<?php

namespace App\Services;

use App\Models\Disease;
use App\Support\ImageStorage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as MlResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Klien ML (FastAPI) untuk prediksi penyakit ikan (NFR-01.2, hard limit 30 detik).
 *
 * Driver: "fake" (dev/test, tanpa Python) atau "http" (ML_BASE_URL).
 * Transport http mengikuti API.md §10.1 — multipart/form-data, bukan JSON.
 *
 * Semua payload (kedua driver) melewati MlPredictionNormalizer, sehingga
 * bentuk keluaran class ini tidak berubah walau model diganti.
 */
class MlClient
{
    public function __construct(private readonly MlPredictionNormalizer $normalizer)
    {
    }

    /**
     * @param  array<int, string>  $imagePaths  s3_key foto scan
     *
     * @return array{
     *   disease_slug: string, disease_name: string, disease_label: ?string,
     *   severity: ?string, confidence: float,
     *   top_predictions: array<int, array{name: string, label: ?string, confidence: float}>,
     *   low_confidence_warning: bool, processing_time_ms: int,
     *   model_version: string, gradcam_url: ?string
     * }
     *
     * @throws MlException
     */
    public function predict(string $scanId, array $imagePaths = [], ?string $fishType = null): array
    {
        return $this->driver() === 'http'
            ? $this->predictViaHttp($scanId, $imagePaths, $fishType)
            : $this->predictFake($scanId);
    }

    private function driver(): string
    {
        return (string) config('ngingu.ml.driver', 'fake');
    }

    /**
     * @param  array<int, string>  $imagePaths
     *
     * @throws MlException
     */
    private function predictViaHttp(string $scanId, array $imagePaths, ?string $fishType): array
    {
        $timeout = (int) config('ngingu.ml.timeout', 30);
        $baseUrl = rtrim((string) config('ngingu.ml.base_url'), '/');
        $images = $this->readImages($imagePaths);

        if ($images === []) {
            throw new MlException('ML_UNAVAILABLE', 'Foto scan tidak ditemukan. Unggah ulang foto.', 503);
        }

        // 1 foto → /predict (§10.1); ≥2 foto → /predict/batch, dirata-ratakan model (§10.5).
        $isBatch = count($images) > 1;
        $endpoint = $isBatch ? '/predict/batch' : '/predict';
        $fileField = $isBatch ? 'images' : 'image';

        $request = Http::connectTimeout((int) config('ngingu.ml.connect_timeout', 5))
            ->timeout($timeout)
            ->withHeaders(['Accept' => 'application/json']);

        foreach ($images as $image) {
            $request = $request->attach($fileField, $image['binary'], $image['filename']);
        }

        $fields = [
            'gradcam' => (bool) config('ngingu.ml.gradcam', true) ? 'true' : 'false',
        ];

        if (! $isBatch && $fishType !== null && trim($fishType) !== '') {
            $fields['fish_type'] = $fishType;
        }

        try {
            $response = $request->post($baseUrl.$endpoint, $fields);
        } catch (ConnectionException $e) {
            // cURL error 28 = timeout sungguhan. Error koneksi lain (DNS tidak
            // resolve, connection refused, dst.) berarti layanan ML tidak
            // terjangkau — kegagalannya instan, jangan dilaporkan sebagai
            // "melewati batas waktu" yang menyesatkan (insiden 23/10: backend &
            // ML beda Docker network → semua scan gagal tapi pesan bilang
            // "prediksi melewati batas 30 detik").
            $timedOut = str_contains($e->getMessage(), 'error 28')
                || str_contains(strtolower($e->getMessage()), 'timed out');

            if ($timedOut) {
                throw new MlException('ML_TIMEOUT', "Prediksi melewati batas {$timeout} detik. Coba lagi.", 504, $e);
            }

            throw new MlException('ML_UNAVAILABLE', 'Layanan prediksi sedang tidak tersedia. Coba lagi nanti.', 503, $e);
        }

        if ($response->failed()) {
            throw $this->exceptionFrom($response);
        }

        $payload = $response->json() ?? [];

        if (! is_array($payload)) {
            throw new MlException('ML_UNAVAILABLE', 'Layanan prediksi sedang tidak tersedia. Coba lagi nanti.', 503);
        }

        return $isBatch
            ? $this->normalizer->normalizeBatch($payload, $scanId)
            : $this->normalizer->normalize($payload, $scanId);
    }

    private function exceptionFrom(MlResponse $response): MlException
    {
        $status = $response->status();
        $detail = $response->json('detail');

        if (is_array($detail)) {
            return MlPredictionNormalizer::exception($detail, $status);
        }

        if (is_string($detail) && trim($detail) !== '') {
            return MlPredictionNormalizer::exception(['message' => trim($detail)], $status);
        }

        return new MlException(
            'ML_UNAVAILABLE',
            'Layanan prediksi sedang tidak tersedia. Coba lagi nanti.',
            $status >= 400 && $status < 600 ? $status : 503,
        );
    }

    /**
     * Baca isi foto dari disk sesuai konfigurasi upload (§10.1 butuh file,
     * bukan s3_key — service Python tidak tahu soal storage backend).
     *
     * @param  array<int, string>  $imagePaths
     * @return array<int, array{binary: string, filename: string}>
     *
     * @throws MlException
     */
    private function readImages(array $imagePaths): array
    {
        $storage = app(ImageStorage::class);
        $disk = Storage::disk($storage->disk());

        $images = [];

        foreach (array_values(array_unique($imagePaths)) as $key) {
            if (! is_string($key) || trim($key) === '' || ! $storage->keyExists($key)) {
                continue;
            }

            $binary = $disk->get($key);

            if (is_string($binary) && $binary !== '') {
                $images[] = [
                    'binary' => $binary,
                    'filename' => basename($key) ?: 'photo.jpg',
                ];
            }
        }

        return $images;
    }

    /**
     * @return array{
     *   disease_slug: string, disease_name: string, disease_label: ?string,
     *   severity: ?string, confidence: float,
     *   top_predictions: array<int, array{name: string, label: ?string, confidence: float}>,
     *   low_confidence_warning: bool, processing_time_ms: int,
     *   model_version: string, gradcam_url: ?string
     * }
     */
    private function predictFake(string $scanId): array
    {
        $delayMs = (int) config('ngingu.ml.fake_delay_ms', 0);

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $labels = $this->fakeLabels();
        $primary = $labels[0];

        // Sengaja dibentuk seperti payload service asli, lalu dinormalisasi —
        // adapter-lah yang menentukan bentuk akhir, bukan driver.
        return $this->normalizer->normalize([
            'disease' => [
                'label' => Str::slug($primary['name']),
                'name' => $primary['name'],
                'slug' => Str::slug($primary['name']),
                'severity' => 'sedang',
            ],
            'confidence' => $primary['confidence'],
            'top_predictions' => $labels,
            'low_confidence_warning' => $primary['confidence'] < (float) config('ngingu.scan.low_confidence_threshold', 60),
            'processing_time_ms' => random_int(600, 4200),
            'model_version' => (string) config('ngingu.ml.fake_model_version', 'fake-v1'),
            'gradcam_image' => null,
        ], $scanId);
    }

    /**
     * @return array<int, array{name: string, confidence: float}>
     */
    private function fakeLabels(): array
    {
        $seed = (int) substr(md5((string) uniqid('', true)), 0, 6);

        $confidence = 62 + ($seed % 36);

        $names = Disease::query()->published()->inRandomOrder()->limit(3)->pluck('name')->all();

        if ($names === []) {
            $names = ['Ikan Sehat'];
        }

        $rest = 100 - $confidence;
        $labels = [['name' => $names[0], 'confidence' => (float) $confidence]];

        $remaining = array_slice($names, 1);
        if ($remaining === []) {
            $remaining = ['Tidak diketahui'];
        }

        $split = round($rest / count($remaining), 1);
        foreach ($remaining as $i => $name) {
            $value = $i === count($remaining) - 1
                ? round(100 - $confidence - ($split * (count($remaining) - 1)), 1)
                : $split;

            $labels[] = ['name' => $name, 'confidence' => max(0.1, $value)];
        }

        return $labels;
    }
}
