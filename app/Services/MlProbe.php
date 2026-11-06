<?php

namespace App\Services;

use App\Models\Disease;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as MlResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Uji coba request ke ML service dari panel admin (API.md §10 / FR-10).
 *
 * Berbeda dengan MlClient — yang melempar MlException karena dipakai
 * ProcessScanJob — kelas ini TIDAK PERNAH melempar. Semua kegagalan
 * (service mati, timeout, 422 IMAGE_QUALITY_LOW, dst.) dikembalikan sebagai
 * array, karena justru itulah yang ingin dilihat admin saat menguji.
 *
 * predict() mengembalikan empat lapis sekaligus — ini gunanya sebagai
 * "test request":
 *   1. request   apa yang dikirim backend ke Python
 *   2. raw       balasan mentah dari service Python
 *   3. normalized hasil setelah lapisan adaptasi (MlPredictionNormalizer),
 *               yaitu yang benar-benar dipakai aplikasi
 *   4. disease   resolusi slug -> tabel diseases (FR-10.11)
 */
class MlProbe
{
    public function __construct(private readonly MlPredictionNormalizer $normalizer)
    {
    }

    /**
     * GET /health (FR-10.2). Timeout sengaja pendek supaya halaman admin
     * tidak ikut menunggu bila service sedang mati.
     *
     * @return array{url: string, ok: bool, http_status: ?int, elapsed_ms: int, body: ?array, error: ?string}
     */
    public function health(): array
    {
        $url = rtrim((string) config('ngingu.ml.base_url'), '/').'/health';

        $result = [
            'url' => $url,
            'ok' => false,
            'http_status' => null,
            'elapsed_ms' => 0,
            'body' => null,
            'error' => null,
        ];

        $start = microtime(true);

        try {
            $response = Http::connectTimeout(2)->timeout(5)->acceptJson()->get($url);

            $result['http_status'] = $response->status();
            $result['body'] = $response->json();
            $result['ok'] = $response->successful();
            $result['error'] = $result['ok'] ? null : 'Service menjawab dengan HTTP '.$response->status();
        } catch (\Throwable $e) {
            $result['error'] = $this->connectionMessage($e, $url);
        }

        $result['elapsed_ms'] = (int) round((microtime(true) - $start) * 1000);

        return $result;
    }

    /**
     * POST /predict (API.md §10.1) dengan satu foto dari form admin.
     *
     * @return array{
     *   ok: bool, driver: string, note: ?string, endpoint: string, url: string,
     *   http_status: ?int, elapsed_ms: int, request: array<string, mixed>,
     *   raw: ?array, normalized: ?array, disease: ?array, error: ?array
     * }
     */
    public function predict(UploadedFile $image, ?string $fishType, bool $gradcam): array
    {
        $driver = (string) config('ngingu.ml.driver', 'fake');

        $result = [
            'ok' => false,
            'driver' => $driver,
            'note' => null,
            'endpoint' => '',
            'url' => '',
            'http_status' => null,
            'elapsed_ms' => 0,
            'request' => [
                'filename' => $image->getClientOriginalName(),
                'mime' => (string) ($image->getClientMimeType() ?: 'application/octet-stream'),
                'size_bytes' => (int) $image->getSize(),
                'fish_type' => $fishType !== null && trim($fishType) !== '' ? trim($fishType) : null,
                'gradcam' => $gradcam,
            ],
            'raw' => null,
            'normalized' => null,
            'disease' => null,
            'error' => null,
        ];

        return $driver === 'http'
            ? $this->predictHttp($result, $image, $gradcam)
            : $this->predictFake($result);
    }

    /**
     * Driver fake: tidak ada request HTTP yang dikirim sama sekali, jadi jangan
     * menampilkan endpoint/HTTP status yang mengesankan seolah-olah ada permintaan.
     * Diproduksi oleh MlClient::predictFake() — acak, bukan model ViT.
     */
    private function predictFake(array $result): array
    {
        $result['endpoint'] = '(driver fake — tanpa request HTTP)';
        $result['url'] = '';
        $result['note'] = 'ML_DRIVER=fake — tidak ada request HTTP yang dikirim: foto tidak pernah '
            .'meninggalkan backend dan model ViT tidak dijalankan. Hasilnya hanya data uji semu '
            .'(berubah tiap kali dijalankan). Ubah ML_DRIVER=http di .env lalu jalankan ml-service '
            .'untuk menguji service sungguhan.';

        $start = microtime(true);

        try {
            $normalized = app(MlClient::class)->predict('ml-probe', []);

            $result['ok'] = true;
            $result['normalized'] = $normalized;
            $result['disease'] = $this->resolveDisease($normalized['disease_slug'] ?? null);
        } catch (MlException $e) {
            $result['error'] = [
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                'status' => 0, // tidak ada HTTP yang benar-benar dipanggil
            ];
        }

        $result['elapsed_ms'] = (int) round((microtime(true) - $start) * 1000);

        return $result;
    }

    private function predictHttp(array $result, UploadedFile $image, bool $gradcam): array
    {
        $baseUrl = rtrim((string) config('ngingu.ml.base_url'), '/');

        $result['endpoint'] = 'POST /predict';
        $result['url'] = $baseUrl.'/predict';

        $fields = ['gradcam' => $gradcam ? 'true' : 'false'];
        $fishType = $result['request']['fish_type'];

        if ($fishType !== null) {
            $fields['fish_type'] = $fishType;
        }

        $binary = (string) file_get_contents($image->getRealPath());
        $start = microtime(true);

        try {
            $response = Http::connectTimeout((int) config('ngingu.ml.connect_timeout', 5))
                ->timeout((int) config('ngingu.ml.timeout', 30))
                ->acceptJson()
                ->attach('image', $binary, $image->getClientOriginalName())
                ->post($baseUrl.'/predict', $fields);
        } catch (\Throwable $e) {
            $result['elapsed_ms'] = (int) round((microtime(true) - $start) * 1000);
            $result['error'] = [
                'code' => 'ML_UNAVAILABLE',
                'message' => $this->connectionMessage($e, $baseUrl.'/predict'),
                'status' => 0,
            ];

            return $result;
        }

        $result['elapsed_ms'] = (int) round((microtime(true) - $start) * 1000);
        $result['http_status'] = $response->status();

        $raw = $response->json();
        $result['raw'] = is_array($raw)
            ? $this->shrinkRaw($raw)
            : ['_non_json' => mb_substr($response->body(), 0, 2000)];

        if ($response->failed()) {
            $result['error'] = $this->errorFrom($response);

            return $result;
        }

        if (! is_array($raw)) {
            $result['error'] = [
                'code' => 'ML_UNAVAILABLE',
                'message' => 'Service menjawab tetapi body-nya bukan JSON.',
                'status' => $response->status(),
            ];

            return $result;
        }

        $normalized = $this->normalizer->normalize($raw);

        $result['ok'] = true;
        $result['normalized'] = $normalized;
        $result['disease'] = $this->resolveDisease($normalized['disease_slug'] ?? null);

        return $result;
    }

    /**
     * Terjemahkan error HTTP -> kode+bentuk yang sama seperti MlClient,
     * supaya admin melihat persis kode yang akan diterima aplikasi.
     *
     * @return array{code: string, message: string, status: int}
     */
    private function errorFrom(MlResponse $response): array
    {
        $status = $response->status();
        $detail = $response->json('detail');

        $exception = is_array($detail)
            ? MlPredictionNormalizer::exception($detail, $status)
            : MlPredictionNormalizer::exception(
                ['message' => is_string($detail) && trim($detail) !== '' ? trim($detail) : 'HTTP '.$status],
                $status
            );

        return [
            'code' => $exception->errorCode,
            'message' => $exception->getMessage(),
            'status' => $status,
        ];
    }

    /**
     * GradCAM bisa > 150 KB base64 — jangan masukkan ke session/HTML.
     * Gambar tetap ditampilkan lewat normalized.gradcam_url.
     */
    private function shrinkRaw(array $raw): array
    {
        foreach (['gradcam_image', 'gradcam'] as $key) {
            if (isset($raw[$key]) && is_string($raw[$key]) && $raw[$key] !== '') {
                $raw[$key] = '<data URI '.number_format(strlen($raw[$key])).' byte — lihat kartu "Hasil Aplikasi">';
            }
        }

        return $raw;
    }

    /**
     * Cek slug prediksi ke tabel diseases — bukti FR-10.11 (label map sinkron).
     * Urutan pencarian sama dengan ProcessScanJob::resolveDisease(): slug dulu, lalu nama.
     *
     * @return array{found: bool, disease_id: ?int, slug: ?string, name: ?string, severity: ?string, hint: ?string}
     */
    private function resolveDisease(?string $slug): array
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return [
                'found' => false,
                'disease_id' => null,
                'slug' => null,
                'name' => null,
                'severity' => null,
                'hint' => 'Prediksi tidak menghasilkan slug penyakit.',
            ];
        }

        $disease = Disease::query()->where('is_published', true)->where('slug', $slug)->first()
            ?? Disease::query()->where('is_published', true)->where('name', $slug)->first();

        if ($disease === null) {
            return [
                'found' => false,
                'disease_id' => null,
                'slug' => $slug,
                'name' => null,
                'severity' => null,
                'hint' => 'Slug "'.$slug.'" tidak ditemukan di tabel diseases (atau belum dipublikasikan) — '
                    .'hasil scan akan tampil "Tidak diketahui". '
                    .'Pastikan DiseaseSeeder sudah jalan, lalu php artisan ml:sync-label-map.',
            ];
        }

        return [
            'found' => true,
            'disease_id' => (int) $disease->id,
            'slug' => $disease->slug,
            'name' => $disease->name,
            'severity' => $disease->severity,
            'hint' => null,
        ];
    }

    private function connectionMessage(\Throwable $e, string $url): string
    {
        return $e instanceof ConnectionException
            ? 'Tidak bisa terhubung ke '.$url.' — '.$e->getMessage()
            : $e->getMessage();
    }
}
