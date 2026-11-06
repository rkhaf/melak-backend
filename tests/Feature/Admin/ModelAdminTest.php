<?php

namespace Tests\Feature\Admin;

use App\Models\Disease;
use App\Models\MlDataset;
use App\Models\MlModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ModelAdminTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_upload_model_version_stores_file_and_records_audit(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('admin.models.store'), [
            'version' => 'v1.0.0',
            'model_file' => UploadedFile::fake()->create('model_v1.onnx', 512),
            'accuracy_pct' => 92.5,
            'disease_labels' => "White Spot (Ich)\nColumnaris",
        ])->assertRedirect(route('admin.models.index'));

        $model = MlModel::where('version', 'v1.0.0')->firstOrFail();

        $this->assertFalse($model->is_active);
        $this->assertSame(92.5, $model->accuracy_pct);
        $this->assertSame(['White Spot (Ich)', 'Columnaris'], $model->disease_labels);
        $this->assertSame($admin->id, $model->uploaded_by);
        Storage::disk('local')->assertExists($model->s3_path);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'model.uploaded',
            'model_id' => $model->id,
        ]);
    }

    public function test_upload_rejects_duplicate_version_and_bad_extension(): void
    {
        MlModel::create(['version' => 'v1.0.0', 'filename' => 'x.onnx', 'is_active' => false]);
        $this->actingAsAdmin();

        $this->post(route('admin.models.store'), [
            'version' => 'v1.0.0',
            'model_file' => UploadedFile::fake()->create('model.onnx', 100),
        ])->assertSessionHasErrors('version');

        $this->post(route('admin.models.store'), [
            'version' => 'v2.0.0',
            'model_file' => UploadedFile::fake()->create('malware.exe', 100),
        ])->assertSessionHasErrors('model_file');
    }

    public function test_activate_switches_active_version_exclusively(): void
    {
        $admin = $this->createAdmin();
        $v1 = MlModel::create(['version' => 'v1', 'filename' => 'a.onnx', 'is_active' => true, 'activated_at' => now()->subDay()]);
        $v2 = MlModel::create(['version' => 'v2', 'filename' => 'b.onnx', 'is_active' => false]);

        $this->actingAs($admin)->post(route('admin.models.activate', $v2))->assertRedirect();

        $this->assertFalse($v1->fresh()->is_active);
        $this->assertTrue($v2->fresh()->is_active);
        $this->assertNotNull($v2->fresh()->activated_at);

        $this->assertDatabaseHas('audit_logs', ['action' => 'model.activated', 'model_id' => $v2->id]);

        $this->actingAs($admin)->post(route('admin.models.activate', $v2))->assertSessionHas('info');
    }

    public function test_rollback_restores_previous_version(): void
    {
        $admin = $this->createAdmin();
        $v1 = MlModel::create(['version' => 'v1', 'filename' => 'a.onnx', 'is_active' => true, 'activated_at' => now()->subDay()]);
        MlModel::create(['version' => 'v2', 'filename' => 'b.onnx', 'is_active' => false]);

        $v2 = MlModel::where('version', 'v2')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.models.activate', $v2));

        $this->actingAs($admin)->post(route('admin.models.rollback'))->assertRedirect();

        $this->assertTrue($v1->fresh()->is_active);
        $this->assertFalse($v2->fresh()->is_active);

        $this->assertDatabaseHas('audit_logs', ['action' => 'model.rollback', 'model_id' => $v1->id]);
    }

    public function test_rollback_without_previous_version_shows_info(): void
    {
        $admin = $this->createAdmin();
        MlModel::create(['version' => 'v1', 'filename' => 'a.onnx', 'is_active' => true, 'activated_at' => now()]);

        $this->actingAs($admin)->post(route('admin.models.rollback'))->assertSessionHas('info');
    }

    public function test_active_model_cannot_be_deleted_and_inactive_can(): void
    {
        $admin = $this->createAdmin();
        $active = MlModel::create(['version' => 'v1', 'filename' => 'a.onnx', 'is_active' => true, 's3_path' => 'ml-models/a.onnx']);
        $inactive = MlModel::create(['version' => 'v2', 'filename' => 'b.onnx', 'is_active' => false, 's3_path' => 'ml-models/b.onnx']);
        Storage::disk('local')->put('ml-models/b.onnx', 'weights');

        $this->actingAs($admin)->delete(route('admin.models.destroy', $active))
            ->assertSessionHasErrors('model');

        $this->actingAs($admin)->delete(route('admin.models.destroy', $inactive))
            ->assertRedirect();

        $this->assertNotNull($active->fresh());
        $this->assertNull($inactive->fresh());
        Storage::disk('local')->assertMissing('ml-models/b.onnx');
        $this->assertDatabaseHas('audit_logs', ['action' => 'model.deleted']);
    }

    public function test_upload_and_delete_dataset_images(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('admin.datasets.store'), [
            'images' => [
                UploadedFile::fake()->image('sample1.png', 100, 100),
                UploadedFile::fake()->image('sample2.jpg', 100, 100),
            ],
            'label' => 'white_spot',
        ])->assertRedirect();

        $this->assertSame(2, MlDataset::count());

        foreach (MlDataset::all() as $dataset) {
            Storage::disk('local')->assertExists($dataset->path);
            $this->assertSame('white_spot', $dataset->label);
            $this->assertSame($admin->id, $dataset->uploaded_by);
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'dataset.uploaded']);

        $dataset = MlDataset::firstOrFail();
        $this->delete(route('admin.datasets.destroy', $dataset))->assertRedirect();

        $this->assertNull($dataset->fresh());
        Storage::disk('local')->assertMissing($dataset->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'dataset.deleted']);
    }

    public function test_dataset_upload_requires_at_least_one_image(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.datasets.store'), ['label' => 'x'])
            ->assertSessionHasErrors('images');
    }

    // ─────────────────────────────────────────── test request ML (API.md §10)

    public function test_index_renders_the_ml_test_form(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('Test Permintaan ML')
            ->assertSee(route('admin.models.test'), false)
            ->assertSee(route('admin.models.status'), false);
    }

    public function test_ml_test_requires_an_image(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.models.test'), [])
            ->assertSessionHasErrors('image');
    }

    public function test_regular_user_cannot_run_ml_test(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)->post(route('admin.models.test'), [
            'image' => UploadedFile::fake()->image('ikan.jpg', 50, 50),
        ])->assertRedirect(route('admin.login'));

        $this->assertNull(session('ml_test'));
    }

    public function test_ml_test_shows_raw_response_and_disease_resolution(): void
    {
        config(['ngingu.ml.driver' => 'http', 'ngingu.ml.base_url' => 'http://ml.test:8009']);
        Http::fake(['*' => Http::response($this->mlPayload(), 200)]);

        Disease::create([
            'name' => 'Infeksi Bakteri Aeromonas (Aeromoniasis)',
            'slug' => 'aeromoniasis',
            'category' => 'bacteria',
            'severity' => 'berat',
            'is_published' => true,
        ]);

        $admin = $this->actingAsAdmin();

        $this->post(route('admin.models.test'), [
            'image' => UploadedFile::fake()->image('ikan.jpg', 300, 300),
            'fish_type' => 'Lele',
            'gradcam' => '1',
        ])->assertRedirect();

        $test = session('ml_test');

        $this->assertTrue($test['ok']);
        $this->assertSame('http', $test['driver']);
        $this->assertSame('POST /predict', $test['endpoint']);
        $this->assertSame(200, $test['http_status']);

        // 1. request terekam apa adanya
        $this->assertSame('ikan.jpg', $test['request']['filename']);
        $this->assertSame('Lele', $test['request']['fish_type']);
        $this->assertTrue($test['request']['gradcam']);

        // 2. response mentah — base64 GradCAM dipadatkan agar session tetap ringan
        $this->assertSame('vit-v1.0.0', $test['raw']['model_version']);
        $this->assertStringContainsString('data URI', $test['raw']['gradcam_image']);

        // 3. hasil lapisan adaptasi (yang dipakai aplikasi)
        $this->assertSame('aeromoniasis', $test['normalized']['disease_slug']);
        $this->assertSame(87.3, $test['normalized']['confidence']);
        $this->assertSame('berat', $test['normalized']['severity']);
        $this->assertNotNull($test['normalized']['gradcam_url']);

        // 4. resolusi slug -> tabel diseases (FR-10.11)
        $this->assertTrue($test['disease']['found']);
        $this->assertSame('Infeksi Bakteri Aeromonas (Aeromoniasis)', $test['disease']['name']);
        $this->assertSame('aeromoniasis', $test['disease']['slug']);

        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'model.tested']);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://ml.test:8009/predict');

        // Kartu hasil ikut tampil pada request berikutnya (umur flash 1 request).
        $this->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('Hasil Aplikasi')
            ->assertSee('Response Mentah')
            ->assertSee('Resolusi ke tabel diseases')
            ->assertSee('data URI')
            ->assertSee('HTTP 200')
            ->assertSee('Infeksi Bakteri Aeromonas (Aeromoniasis)');
    }

    public function test_ml_test_reports_unreachable_service_instead_of_failing(): void
    {
        // Port 1 tidak pernah punya listener -> connection refused, tanpa jaringan luar.
        config([
            'ngingu.ml.driver' => 'http',
            'ngingu.ml.base_url' => 'http://127.0.0.1:1',
            'ngingu.ml.connect_timeout' => 1,
            'ngingu.ml.timeout' => 2,
        ]);

        $this->actingAsAdmin();

        $this->post(route('admin.models.test'), [
            'image' => UploadedFile::fake()->image('ikan.jpg', 50, 50),
        ])->assertRedirect();

        $test = session('ml_test');

        $this->assertFalse($test['ok']);
        $this->assertNull($test['http_status']);
        $this->assertNull($test['normalized']);
        $this->assertSame('ML_UNAVAILABLE', $test['error']['code']);
        $this->assertNotSame('', $test['error']['message']);

        $this->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('GAGAL')
            ->assertSee('ML_UNAVAILABLE');
    }

    public function test_ml_test_maps_service_error_code(): void
    {
        config(['ngingu.ml.driver' => 'http', 'ngingu.ml.base_url' => 'http://ml.test:8009']);
        Http::fake(['*' => Http::response([
            'detail' => [
                'code' => 'IMAGE_QUALITY_LOW',
                'message' => 'Gambar terlalu blur.',
                'suggestions' => ['Fokuskan kamera'],
            ],
        ], 422)]);

        $this->actingAsAdmin();

        $this->post(route('admin.models.test'), [
            'image' => UploadedFile::fake()->image('blur.jpg', 50, 50),
        ])->assertRedirect();

        $test = session('ml_test');

        $this->assertFalse($test['ok']);
        $this->assertSame(422, $test['http_status']);
        $this->assertSame('IMAGE_QUALITY_LOW', $test['error']['code']);
        $this->assertSame(422, $test['error']['status']);
        $this->assertStringContainsString('blur', $test['error']['message']);
        $this->assertStringContainsString('Fokuskan kamera', $test['error']['message']);
    }

    public function test_ml_test_with_fake_driver_makes_no_python_request(): void
    {
        config(['ngingu.ml.driver' => 'fake']);
        Http::fake();

        Disease::create(['name' => 'Ikan Sehat', 'slug' => 'ikan-sehat', 'is_published' => true]);

        $this->actingAsAdmin();

        $this->post(route('admin.models.test'), [
            'image' => UploadedFile::fake()->image('ikan.png', 50, 50),
        ])->assertRedirect();

        $test = session('ml_test');

        $this->assertTrue($test['ok']);
        $this->assertSame('fake', $test['driver']);
        $this->assertNotNull($test['normalized']);
        $this->assertNotNull($test['note']);

        // Tidak boleh mengesankan ada request HTTP yang benar-benar dikirim.
        $this->assertNull($test['http_status']);
        $this->assertSame('', $test['url']);
        $this->assertStringContainsString('tanpa request HTTP', $test['endpoint']);
        $this->assertStringContainsString('ML_DRIVER=fake', $test['note']);

        // Admin harus sadar tidak ada request sungguhan yang dikirim.
        Http::assertNothingSent();

        $this->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('ML_DRIVER=fake')
            ->assertSee('BERHASIL')
            ->assertDontSee('HTTP 200')
            ->assertDontSee('POST /predict');
    }

    public function test_status_endpoint_reports_service_health(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'healthy',
            'model_version' => 'vit-v1.0.0',
            'model_format' => 'safetensors',
            'uptime_seconds' => 12,
            'model_loaded' => true,
        ], 200)]);

        $this->actingAsAdmin();

        $this->get(route('admin.models.status'))->assertRedirect();

        $health = session('ml_health');

        $this->assertTrue($health['ok']);
        $this->assertSame(200, $health['http_status']);
        $this->assertSame('healthy', $health['body']['status']);
        $this->assertSame('vit-v1.0.0', $health['body']['model_version']);
        $this->assertStringEndsWith('/health', $health['url']);

        $this->get(route('admin.models.index'))
            ->assertOk()
            ->assertSee('Service sehat')
            ->assertSee('vit-v1.0.0');
    }

    public function test_status_endpoint_surfaces_connection_failure(): void
    {
        config(['ngingu.ml.base_url' => 'http://127.0.0.1:1']);

        $this->actingAsAdmin();

        $this->get(route('admin.models.status'))->assertRedirect();

        $health = session('ml_health');

        $this->assertFalse($health['ok']);
        $this->assertNotNull($health['error']);
        $this->assertNull($health['body']);
    }

    /**
     * Balasan service Python yang persis seperti kontrak API.md §10.1.
     *
     * @return array<string, mixed>
     */
    private function mlPayload(): array
    {
        // PNG 1x1 valid — wajib agar ImageStorage::storeGradcam mau menyimpannya.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        return [
            'disease' => [
                'label' => 'aeromoniasis',
                'name' => 'Infeksi Bakteri Aeromonas (Aeromoniasis)',
                'slug' => 'aeromoniasis',
                'severity' => 'berat',
            ],
            'confidence' => 87.3,
            'top_predictions' => [
                ['label' => 'aeromoniasis', 'name' => 'Infeksi Bakteri Aeromonas (Aeromoniasis)', 'confidence' => 87.3],
                ['label' => 'healthy', 'name' => 'Ikan Sehat', 'confidence' => 12.7],
            ],
            'low_confidence_warning' => false,
            'gradcam_image' => 'data:image/png;base64,'.base64_encode((string) $png),
            'processing_time_ms' => 1243,
            'model_version' => 'vit-v1.0.0',
        ];
    }
}
