<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\ScanResult;
use App\Models\User;
use App\Notifications\ScanResult as ScanResultNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Link tombol email "Lihat Hasil" (FR-06.1) harus membuka halaman web,
 * bukan endpoint API yang membalas JSON/401.
 */
class ScanWebPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedScan(): array
    {
        $user = User::factory()->create();

        $disease = Disease::factory()->create([
            'name' => 'Bercak Bakteri pada Tomat',
            'slug' => 'tomato-bacterial-spot-web',
            'severity' => 'berat',
            'description' => 'Bercak gelap pada daun tomat.',
            'symptoms' => "Bercak gelap pada daun\nDaun menguning",
        ]);
        $disease->treatments()->create([
            'step_order' => 1,
            'title' => 'Buang daun bergejala',
            'description' => 'Pangkas dan buang daun terinfeksi.',
            'duration_days' => 5,
        ]);
        $disease->preventions()->create(['step_order' => 1, 'description' => 'Gunakan benih sehat.']);

        $scan = PlantScan::factory()->create([
            'user_id' => $user->id,
            'status' => 'done',
            'plant_type' => 'Tomat',
        ]);

        ScanResult::create([
            'scan_id' => $scan->id,
            'disease_id' => $disease->id,
            'confidence_score' => 88.5,
            'top_predictions' => [['name' => 'Bercak Bakteri pada Tomat', 'confidence' => 88.5]],
            'low_confidence_warning' => false,
            'model_version' => 'v1',
        ]);

        return [$user, $scan, $disease];
    }

    public function test_email_action_points_to_signed_web_page_not_api(): void
    {
        [$user, $scan] = $this->seedScan();

        $mail = (new ScanResultNotification($scan, $scan->result->disease, 88.5))->toMail($user);

        $this->assertStringStartsWith(rtrim(config('app.url'), '/'), $mail->actionUrl);
        $this->assertStringContainsString('/scans/'.$scan->id, $mail->actionUrl);
        $this->assertStringNotContainsString('/api/scans', $mail->actionUrl);
        $this->assertStringContainsString('signature=', $mail->actionUrl);

        // Link dari email → halaman web yang bisa dibaca manusia.
        $this->get($mail->actionUrl)
            ->assertOk()
            ->assertSee('Bercak Bakteri pada Tomat')
            ->assertSee('Buang daun bergejala');
    }

    public function test_page_renders_scan_info_result_and_guides(): void
    {
        [, $scan] = $this->seedScan();

        $this->get(URL::signedRoute('scans.show', ['id' => $scan->id]))
            ->assertOk()
            ->assertSee('Hasil Prediksi')
            ->assertSee('88.5%')
            ->assertSee('Tomat')
            ->assertSee('Informasi Scan')
            ->assertSee('Pencegahan')
            ->assertDontSee('/api/scans');
    }

    public function test_unsigned_link_and_unknown_scan_are_rejected(): void
    {
        [, $scan] = $this->seedScan();

        // Tanpa tanda tangan (dicopy manual / diakses langsung) → ditolak.
        $this->get('/scans/'.$scan->id)->assertStatus(403);

        // ID tidak ada → 404 (link tetap bertanda tangan).
        $this->get(URL::signedRoute('scans.show', ['id' => '00000000-0000-0000-0000-000000000000']))
            ->assertStatus(404);
    }

    public function test_failed_scan_shows_error_banner(): void
    {
        $user = User::factory()->create();
        $scan = PlantScan::factory()->failed()->create([
            'user_id' => $user->id,
            'error_message' => 'Gambar tidak terbaca.',
        ]);

        $this->get(URL::signedRoute('scans.show', ['id' => $scan->id]))
            ->assertOk()
            ->assertSee('Scan gagal.')
            ->assertSee('Gambar tidak terbaca.');
    }
}
