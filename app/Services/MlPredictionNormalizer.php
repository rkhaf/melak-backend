<?php

namespace App\Services;

use App\Support\ImageStorage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lapisan adaptasi (anti-corruption layer) antara ML service dan backend.
 *
 * normalize() selalu menghasilkan bentuk array yang SAMA, apa pun bentuk
 * payload keluaran model. Ganti arsitektur/label/format GradCAM di Python
 * tanpa menyentuh ProcessScanJob maupun ScanController.
 *
 * Variasi yang ditangani:
 *  - disease         : object {label,name,slug,severity} | string | null
 *  - confidence      : persen 0-100 | fraksi 0-1 | absen (fallback top_predictions)
 *  - top_predictions : {name,confidence} | {label,name,confidence} | {probability} | string | absen
 *  - gradcam         : gradcam_image (base64) | gradcam_url (URL) | gradcam{image|url} | data URI | null
 *  - low_confidence  : bool | absen (dihitung dari threshold config)
 */
class MlPredictionNormalizer
{
    private const UNKNOWN = 'Tidak diketahui';

    private const UNKNOWN_SLUG = 'tidak-diketahui';

    /** Kolom scan_images.gradcam_url bertipe varchar(255). */
    private const MAX_URL_LENGTH = 255;

    public function __construct(private readonly ImageStorage $storage)
    {
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
    public function normalize(array $payload, ?string $scanId = null): array
    {
        $predictions = $this->predictions($payload);
        $disease = $this->disease($payload, $predictions);
        $confidence = $this->confidence($payload, $predictions);
        $threshold = (float) config('ngingu.scan.low_confidence_threshold', 60);

        return [
            'disease_slug' => $disease['slug'],
            'disease_name' => $disease['name'],
            'disease_label' => $disease['label'],
            'severity' => $disease['severity'],
            'confidence' => $confidence,
            'top_predictions' => $predictions,
            'low_confidence_warning' => is_bool($payload['low_confidence_warning'] ?? null)
                ? $payload['low_confidence_warning']
                : $confidence < $threshold,
            'processing_time_ms' => max(0, (int) ($payload['processing_time_ms'] ?? 0)),
            'model_version' => (string) ($payload['model_version'] ?? 'unknown'),
            'gradcam_url' => $this->gradcam($payload, $scanId),
        ];
    }

    /**
     * Payload batch (API.md §10.5 / FR-10.5): hasil per foto + blok aggregated.
     * Dirata-ratakan di sisi model — sini tinggal ambil hasil akhirnya saja.
     */
    public function normalizeBatch(array $payload, ?string $scanId = null): array
    {
        $aggregate = is_array($payload['aggregated'] ?? null) ? $payload['aggregated'] : null;

        if ($aggregate === null) {
            // Bukan payload batch (mis. service versi lama) → pakai jalur biasa.
            return $this->normalize($payload, $scanId);
        }

        $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];
        $best = $results[(int) ($aggregate['best_photo_index'] ?? 0)] ?? ($results[0] ?? []);

        return $this->normalize([
            'disease' => $aggregate['disease'] ?? ($best['disease'] ?? null),
            'confidence' => $aggregate['confidence'] ?? ($best['confidence'] ?? null),
            'top_predictions' => $best['top_predictions'] ?? [],
            'low_confidence_warning' => $aggregate['low_confidence_warning'] ?? null,
            'gradcam_image' => $best['gradcam_image'] ?? null,
            'gradcam_url' => $best['gradcam_url'] ?? null,
            'processing_time_ms' => $payload['processing_time_ms'] ?? 0,
            'model_version' => $best['model_version'] ?? null,
        ], $scanId);
    }

    /**
     * Terjemahkan detail error ML service (API.md §10.2) → MlException.
     * format: {"detail": {"code": "...", "message": "...", "suggestions": [...]}}
     *
     * @param  array<string, mixed>  $detail
     */
    public static function exception(array $detail, int $status): MlException
    {
        $code = Str::upper((string) ($detail['code'] ?? ''));
        $message = trim((string) ($detail['message'] ?? ''));

        $suggestions = array_values(array_filter(array_map(
            'strval',
            is_array($detail['suggestions'] ?? null) ? $detail['suggestions'] : []
        )));

        if ($suggestions !== []) {
            $message = trim($message.' '.implode(' ', $suggestions));
        }

        return match ($code) {
            'UNSUPPORTED_MEDIA_TYPE' => new MlException(
                'UNSUPPORTED_MEDIA_TYPE',
                $message !== '' ? $message : 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.',
                400,
            ),
            'IMAGE_QUALITY_LOW' => new MlException(
                'IMAGE_QUALITY_LOW',
                $message !== '' ? $message : 'Kualitas gambar terlalu rendah untuk dianalisis.',
                422,
            ),
            'MODEL_NOT_LOADED' => new MlException(
                'ML_UNAVAILABLE',
                $message !== '' ? $message : 'Model prediksi belum siap. Coba lagi beberapa saat.',
                503,
            ),
            'INFERENCE_TIMEOUT', 'ML_TIMEOUT' => new MlException(
                'ML_TIMEOUT',
                $message !== '' ? $message : 'Prediksi melewati batas 30 detik. Coba lagi.',
                504,
            ),
            default => new MlException(
                $code !== '' ? $code : 'ML_UNAVAILABLE',
                $message !== '' ? $message : 'Layanan prediksi sedang tidak tersedia. Coba lagi nanti.',
                $status >= 400 && $status < 600 ? $status : 503,
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{name: string, label: ?string, confidence: float}>
     */
    private function predictions(array $payload): array
    {
        $rows = $payload['top_predictions'] ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $predictions = [];

        foreach ($rows as $row) {
            if (is_string($row)) {
                $predictions[] = ['name' => $row, 'label' => null, 'confidence' => 0.0];

                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $name = $this->first([
                $row['name'] ?? null,
                $row['label'] ?? null,
                $row['class'] ?? null,
                $row['disease'] ?? null,
            ]);

            if ($name === null) {
                continue;
            }

            $label = $this->first([$row['label'] ?? null, $row['class'] ?? null]);

            $predictions[] = [
                'name' => $name,
                'label' => $label !== null && $label !== $name ? $label : null,
                'confidence' => $this->percent($row['confidence'] ?? $row['probability'] ?? $row['prob'] ?? null) ?? 0.0,
            ];
        }

        return $predictions;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{name: string, label: ?string, confidence: float}>  $predictions
     * @return array{slug: string, name: string, label: ?string, severity: ?string}
     */
    private function disease(array $payload, array $predictions): array
    {
        $raw = $payload['disease'] ?? $payload['primary_disease'] ?? null;

        // Bentuk lama: "disease" berupa string nama penyakit.
        if (is_string($raw)) {
            $raw = ['name' => $raw];
        }

        if (! is_array($raw)) {
            $raw = [];
        }

        // label dipakai untuk penamaan & slug; label model jauh lebih akurat
        // daripada Str::slug(nama) yang penuh spasi/kurung.
        $label = $this->first([
            $raw['label'] ?? null,
            $payload['label'] ?? null,
            $predictions[0]['label'] ?? null,
        ]);

        $name = $this->first([
            $raw['name'] ?? null,
            $predictions[0]['name'] ?? null,
            $label,
        ]) ?? self::UNKNOWN;

        // slug label_map (baru) → slug eksplisit payload → label → slug dari nama.
        $slug = $this->first([
            $raw['slug'] ?? null,
            $payload['slug'] ?? null,
            $label !== null ? Str::slug($label) : null,
            Str::slug($name),
        ]) ?? self::UNKNOWN_SLUG;

        return [
            'slug' => $slug,
            'name' => $name,
            'label' => $label,
            'severity' => $this->first([$raw['severity'] ?? null, $payload['severity'] ?? null]),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{name: string, label: ?string, confidence: float}>  $predictions
     */
    private function confidence(array $payload, array $predictions): float
    {
        $value = $this->percent($payload['confidence'] ?? null);

        if ($value === null) {
            $value = $predictions[0]['confidence'] ?? 0.0;
        }

        return round($value, 1);
    }

    /**
     * Samakan skala ke persen 0-100. Model bisa mengirim fraksi softmax (0-1)
     * maupun persen — keduanya lazim. Nilai ≤1 dianggap fraksi.
     */
    private function percent(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (float) $value;

        if ($value <= 1.0) {
            $value *= 100;
        }

        return round(min(max($value, 0.0), 100.0), 1);
    }

    /**
     * GradCAM: base64 tidak muat di varchar(255), jadi binarnya disimpan
     * sebagai file dan hanya URL-nya yang dikembalikan.
     *
     * @param  array<string, mixed>  $payload
     */
    private function gradcam(array $payload, ?string $scanId): ?string
    {
        $nested = is_array($payload['gradcam'] ?? null) ? $payload['gradcam'] : [];

        $url = $this->first([
            $payload['gradcam_url'] ?? null,
            $nested['url'] ?? null,
            $nested['gradcam_url'] ?? null,
        ]);

        $image = $this->first([
            $payload['gradcam_image'] ?? null,
            $nested['image'] ?? null,
            $nested['base64'] ?? null,
        ]);

        // URL jadi (service lama / service yang sudah simpan sendiri).
        if (is_string($url) && $url !== '') {
            if (! str_starts_with($url, 'data:')) {
                return strlen($url) <= self::MAX_URL_LENGTH ? $url : null;
            }

            $image = $url;
        }

        if (! is_string($image) || $image === '') {
            return null;
        }

        return $this->store($this->decode($image), $scanId);
    }

    private function decode(string $raw): ?string
    {
        if (str_contains($raw, 'base64,')) {
            $raw = substr($raw, strpos($raw, 'base64,') + 7);
        }

        $binary = base64_decode((string) preg_replace('/\s+/', '', $raw), true);

        return $binary === false || $binary === '' ? null : $binary;
    }

    private function store(?string $binary, ?string $scanId): ?string
    {
        if ($binary === null) {
            return null;
        }

        try {
            return $this->storage->storeGradcam($binary, $scanId);
        } catch (\Throwable $e) {
            // GradCAM bersifat pelengkap — kegagalannya tidak boleh menggagalkan prediksi.
            Log::warning('GradCAM tidak dapat disimpan', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function first(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_int($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
