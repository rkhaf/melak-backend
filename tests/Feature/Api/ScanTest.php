<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessScanJob;
use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    private function uploadPayload(int $count = 2, array $overrides = []): array
    {
        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $images[] = UploadedFile::fake()->image("tanaman-{$i}.jpg", 640, 480);
        }

        return array_merge([
            'images' => $images,
            'plant_type' => 'Tomat',
            'symptoms_desc' => 'Bercak gelap pada daun',
        ], $overrides);
    }

    public function test_create_scan_returns_202_and_processes_asynchronously(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/api/scans', $this->uploadPayload());

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.images_count', 2)
            ->assertJsonPath('data.estimated_seconds', 10);

        $scanId = $response->json('data.scan_id');
        $scan = PlantScan::find($scanId);

        $this->assertNotNull($scan);
        $this->assertSame('done', $scan->status);
        $this->assertSame(2, $scan->images()->count());
        $this->assertNotNull($scan->result);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->user->id, 'type' => 'scan_result']);
    }

    public function test_create_scan_validates_image_count_and_format(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/scans', $this->uploadPayload(6))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['images']]);

        $this->actingAs($this->user)
            ->postJson('/api/scans', ['images' => [UploadedFile::fake()->create('dokumen.pdf', 100)]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['images.0']]);
    }

    public function test_list_scans_returns_meta_and_supports_filters(): void
    {
        $done = PlantScan::factory()->for($this->user)->create(['status' => 'done']);
        $done->images()->create(['s3_key' => 'scans/x/a.jpg', 's3_url' => 'http://x/a.jpg', 'is_primary' => true, 'order_index' => 0]);
        PlantScan::factory()->for(User::factory()->create())->create(['status' => 'done']);

        $this->actingAs($this->user)
            ->getJson('/api/scans')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.scan_id', $done->id)
            ->assertJsonStructure(['data' => [['scan_id', 'thumbnail_url', 'plant_type', 'garden', 'disease', 'confidence', 'status', 'created_at']]]);

        $this->actingAs($this->user)
            ->getJson('/api/scans?plant_type=Cabai')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_show_returns_payload_immediately_with_wait_zero(): void
    {
        $scan = PlantScan::factory()->for($this->user)->create(['status' => 'processing']);

        $response = $this->actingAs($this->user)->getJson("/api/scans/{$scan->id}?wait=0");

        $response->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.disease', null)
            ->assertJsonPath('data.top_predictions', null)
            ->assertJsonPath('data.poll_after_ms', 2000);
    }

    public function test_show_returns_full_result_when_done(): void
    {
        $disease = Disease::factory()->create();
        $scan = PlantScan::factory()->for($this->user)->create(['status' => 'processing']);

        ProcessScanJob::dispatchSync($scan->id);

        $scan->refresh();

        $this->assertSame('done', $scan->status);

        $response = $this->actingAs($this->user)->getJson("/api/scans/{$scan->id}?wait=5");

        $response->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.plant_type', $scan->plant_type)
            ->assertJsonPath('data.top_predictions.0.name', $disease->name)
            ->assertJsonPath('data.low_confidence_warning', false)
            ->assertJsonPath('data.model_version', 'fake-v1');

        $this->assertArrayNotHasKey('poll_after_ms', $response->json('data'));
        $this->assertNotNull($response->json('data.treatment_url'));
    }

    public function test_show_blocks_others_scan_and_missing_scan(): void
    {
        $other = PlantScan::factory()->for(User::factory()->create())->create(['status' => 'done']);

        $this->actingAs($this->user)
            ->getJson("/api/scans/{$other->id}?wait=0")
            ->assertStatus(403);

        $this->actingAs($this->user)
            ->getJson('/api/scans/6f9b2c1e-8a44-4f0d-9c3a-1b2d3e4f5a6b?wait=0')
            ->assertStatus(404);
    }

    public function test_delete_scan_removes_data_and_files(): void
    {
        $scan = PlantScan::factory()->for($this->user)->create(['status' => 'done']);
        $image = $scan->images()->create(['s3_key' => 'scans/x/b.jpg', 's3_url' => 'http://x/b.jpg', 'is_primary' => true, 'order_index' => 0]);

        Storage::disk('public')->put($image->s3_key, 'isi');

        $this->actingAs($this->user)->deleteJson("/api/scans/{$scan->id}")->assertNoContent();

        $this->assertDatabaseMissing('plant_scans', ['id' => $scan->id]);
        Storage::disk('public')->assertMissing($image->s3_key);
    }

    public function test_delete_others_scan_is_forbidden(): void
    {
        $other = PlantScan::factory()->for(User::factory()->create())->create();

        $this->actingAs($this->user)->deleteJson("/api/scans/{$other->id}")->assertStatus(403);
    }

    public function test_export_history_as_csv(): void
    {
        $scan = PlantScan::factory()->for($this->user)->create(['status' => 'done']);
        $scan->images()->create(['s3_key' => 'scans/x/c.jpg', 's3_url' => 'http://x/c.jpg', 'is_primary' => true, 'order_index' => 0]);

        $response = $this->actingAs($this->user)->get('/api/scans/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('tanggal,kebun,jenis_tanaman,penyakit,confidence,tingkat,tindakan', $csv);
        $this->assertStringContainsString((string) $scan->plant_type, $csv);
    }

    public function test_export_single_scan_as_pdf(): void
    {
        $scan = PlantScan::factory()->for($this->user)->create(['status' => 'done']);

        $response = $this->actingAs($this->user)->get("/api/scans/{$scan->id}/export-pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('hasil-scan-'.$scan->id, (string) $response->headers->get('Content-Disposition'));
    }
}
