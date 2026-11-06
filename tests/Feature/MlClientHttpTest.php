<?php

namespace Tests\Feature;

use App\Services\MlClient;
use App\Services\MlException;
use App\Support\ImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Transport HTTP MlClient (API.md §10.1 multipart + §10.2 error mapping).
 * Dipisah dari unit test normalizer supaya kegagalan jelas: adapter vs klien.
 */
class MlClientHttpTest extends TestCase
{
    use RefreshDatabase;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ngingu.ml.driver' => 'http',
            'ngingu.ml.base_url' => 'http://ml.test:8000',
            'ngingu.ml.gradcam' => true,
        ]);

        $this->disk = app(ImageStorage::class)->disk();
        Storage::fake($this->disk);
    }

    public function test_single_image_posts_multipart_to_predict(): void
    {
        $this->putImage('scans/scan-1/0-abc123.jpg');

        Http::fake(['*' => Http::response($this->servicePayload())]);

        $result = app(MlClient::class)->predict('scan-1', ['scans/scan-1/0-abc123.jpg'], 'Lele');

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://ml.test:8000/predict'
                && $request->hasFile('image', null, '0-abc123.jpg');
        });

        $request = Http::recorded()[0][0];
        $this->assertSame('true', $this->field($request, 'gradcam'), 'GradCAM harus diminta (FR-10.8)');
        $this->assertSame('Lele', $this->field($request, 'fish_type'));

        // Bentuk hasil = kontrak internal, bukan bentuk payload Python.
        $this->assertSame('aeromoniasis', $result['disease_slug']);
        $this->assertSame('vit-v1.0.0', $result['model_version']);
    }

    public function test_multiple_images_post_to_batch_endpoint(): void
    {
        $this->putImage('scans/scan-2/0-aaa.jpg');
        $this->putImage('scans/scan-2/1-bbb.jpg');

        Http::fake(['*' => Http::response($this->batchPayload())]);

        $result = app(MlClient::class)->predict(
            'scan-2',
            ['scans/scan-2/0-aaa.jpg', 'scans/scan-2/1-bbb.jpg'],
            null
        );

        Http::assertSent(function (Request $request) {
            $names = collect($request->data())->where('name', 'images');

            return $request->url() === 'http://ml.test:8000/predict/batch'
                && $names->count() === 2;
        });

        // Hasil agregasi (§10.5) yang dipakai, bukan foto pertama.
        $this->assertSame('saprolegniasis', $result['disease_slug']);
        $this->assertSame(74.5, $result['confidence']);
    }

    public function test_missing_image_files_fail_fast_without_http_call(): void
    {
        Http::fake();

        $this->expectException(MlException::class);
        $this->expectExceptionMessage('Foto scan tidak ditemukan');

        app(MlClient::class)->predict('scan-x', ['scans/scan-x/tidak-ada.jpg'], null);
    }

    // ── error mapping (API.md §10.2) ─────────────────────────────────

    public function test_image_quality_low_is_mapped_with_suggestions(): void
    {
        $this->putImage('scans/s/0-a.jpg');

        Http::fake(['*' => Http::response([
            'detail' => [
                'code' => 'IMAGE_QUALITY_LOW',
                'message' => 'Gambar terlalu blur.',
                'suggestions' => ['Pastikan ikan terlihat jelas.'],
            ],
        ], 422)]);

        try {
            app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);
            $this->fail('Seharusnya melempar MlException');
        } catch (MlException $e) {
            $this->assertSame('IMAGE_QUALITY_LOW', $e->errorCode);
            $this->assertSame(422, $e->getCode());
            $this->assertStringContainsString('Pastikan ikan terlihat jelas.', $e->getMessage());
        }
    }

    public function test_model_not_loaded_is_mapped_to_unavailable_503(): void
    {
        $this->putImage('scans/s/0-a.jpg');

        Http::fake(['*' => Http::response([
            'detail' => ['code' => 'MODEL_NOT_LOADED', 'message' => 'Model belum siap'],
        ], 503)]);

        try {
            app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);
            $this->fail('Seharusnya melempar MlException');
        } catch (MlException $e) {
            $this->assertSame('ML_UNAVAILABLE', $e->errorCode);
            $this->assertSame(503, $e->getCode());
        }
    }

    public function test_timeout_is_mapped_to_504(): void
    {
        $this->putImage('scans/s/0-a.jpg');

        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);
            $this->fail('Seharusnya melempar MlException');
        } catch (MlException $e) {
            $this->assertSame('ML_TIMEOUT', $e->errorCode);
            $this->assertSame(504, $e->getCode());
        }
    }

    public function test_connection_failure_is_mapped_to_unavailable_not_timeout(): void
    {
        $this->putImage('scans/s/0-a.jpg');

        Http::fake(function () {
            throw new ConnectionException('cURL error 6: Could not resolve host: ngingu-ml');
        });

        try {
            app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);
            $this->fail('Seharusnya melempar MlException');
        } catch (MlException $e) {
            // Koneksi gagal instan (mis. service beda Docker network sehingga
            // hostname tidak resolve) wajib dilaporkan sebagai layanan tidak
            // tersedia — BUKAN "melewati batas waktu" yang menyesatkan.
            $this->assertSame('ML_UNAVAILABLE', $e->errorCode);
            $this->assertSame(503, $e->getCode());
        }
    }

    public function test_unstructured_error_body_falls_back_to_unavailable(): void
    {
        $this->putImage('scans/s/0-a.jpg');

        Http::fake(['*' => Http::response('<html>Bad Gateway</html>', 502, ['Content-Type' => 'text/html'])]);

        try {
            app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);
            $this->fail('Seharusnya melempar MlException');
        } catch (MlException $e) {
            $this->assertSame('ML_UNAVAILABLE', $e->errorCode);
            $this->assertSame(502, $e->getCode());
            $this->assertNotSame('', $e->getMessage());
        }
    }

    // ── driver parity ────────────────────────────────────────────────

    public function test_http_and_fake_drivers_expose_identical_shape(): void
    {
        $this->putImage('scans/s/0-a.jpg');

        Http::fake(['*' => Http::response($this->servicePayload())]);

        $viaHttp = app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);

        config(['ngingu.ml.driver' => 'fake']);

        $viaFake = app(MlClient::class)->predict('s', ['scans/s/0-a.jpg'], null);

        $this->assertSame(array_keys($viaHttp), array_keys($viaFake));

        // confidence = float (0-100), processing_time_ms = int — di kedua driver.
        foreach (['confidence'] as $float) {
            $this->assertIsFloat($viaHttp[$float]);
            $this->assertIsFloat($viaFake[$float]);
        }

        foreach (['processing_time_ms'] as $int) {
            $this->assertIsInt($viaHttp[$int]);
            $this->assertIsInt($viaFake[$int]);
        }

        foreach (['top_predictions'] as $list) {
            foreach ($viaHttp[$list] as $row) {
                $this->assertIsArray($row);
            }
            foreach ($viaFake[$list] as $row) {
                $this->assertIsArray($row);
            }
        }
    }

    // ── fixtures ─────────────────────────────────────────────────────

    private function servicePayload(): array
    {
        return [
            'disease' => ['label' => 'aeromoniasis', 'name' => 'Infeksi Bakteri Aeromonas', 'slug' => 'aeromoniasis', 'severity' => 'berat'],
            'confidence' => 87.3,
            'top_predictions' => [
                ['label' => 'aeromoniasis', 'name' => 'Infeksi Bakteri Aeromonas', 'confidence' => 87.3],
                ['label' => 'healthy', 'name' => 'Ikan Sehat', 'confidence' => 8.4],
            ],
            'low_confidence_warning' => false,
            'gradcam_image' => null,
            'processing_time_ms' => 835,
            'model_version' => 'vit-v1.0.0',
        ];
    }

    private function batchPayload(): array
    {
        return [
            'results' => [
                ['disease' => ['label' => 'a', 'name' => 'A', 'slug' => 'a'], 'confidence' => 55, 'top_predictions' => [], 'model_version' => 'vit-v1.0.0'],
                ['disease' => ['label' => 'b', 'name' => 'B', 'slug' => 'b'], 'confidence' => 80, 'top_predictions' => [], 'model_version' => 'vit-v1.0.0'],
            ],
            'aggregated' => [
                'disease' => ['label' => 'saprolegniasis', 'name' => 'Saprolegniasis', 'slug' => 'saprolegniasis', 'severity' => 'sedang'],
                'confidence' => 74.5,
                'best_photo_index' => 1,
            ],
            'processing_time_ms' => 3000,
        ];
    }

    private function putImage(string $key): void
    {
        $image = imagecreatetruecolor(8, 8);
        imagefilledrectangle($image, 0, 0, 7, 7, imagecolorallocate($image, 0, 128, 255));

        ob_start();
        imagejpeg($image);
        imagedestroy($image);

        Storage::disk($this->disk)->put($key, (string) ob_get_clean());
    }

    private function field(Request $request, string $name): ?string
    {
        foreach ((array) $request->data() as $part) {
            if (($part['name'] ?? null) === $name) {
                return (string) ($part['contents'] ?? '');
            }
        }

        return null;
    }
}
