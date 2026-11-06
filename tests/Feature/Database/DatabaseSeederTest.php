<?php

namespace Tests\Feature\Database;

use App\Models\Disease;
use App\Models\Faq;
use App\Models\ForumCategory;
use App\Models\HelpContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DatabaseSeeder dijalankan otomatis oleh deploy (CI/CD) tiap kali file
 * seeder berubah — test ini memastikan seedernya selalu bisa jalan dan
 * mengisi data dasar (penyakit FR-09.2.1, bantuan, kategori forum).
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_without_error(): void
    {
        $this->seed();

        // 11 kelas model → ensiklopedia penyakit (FR-10.11 / FR-09.2.1).
        $this->assertSame(11, Disease::count());
        $this->assertTrue(
            Disease::query()->where('slug', 'aeromoniasis')->first()?->treatments()->exists(),
            'Langkah pengobatan wajib ikut ter-seed.',
        );
        $this->assertTrue(
            Disease::query()->where('slug', 'aeromoniasis')->first()?->preventions()->exists(),
            'Langkah pencegahan wajib ikut ter-seed.',
        );

        // Bantuan (FAQ + kontak) & kategori forum.
        $this->assertNotSame(0, HelpContact::count());
        $this->assertNotSame(0, Faq::count());
        $this->assertGreaterThanOrEqual(6, ForumCategory::count());
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed();

        $counts = [
            Disease::count(),
            HelpContact::count(),
            Faq::count(),
            ForumCategory::count(),
            User::count(),
        ];

        $this->seed();

        $this->assertSame($counts, [
            Disease::count(),
            HelpContact::count(),
            Faq::count(),
            ForumCategory::count(),
            User::count(),
        ], 'Menjalankan seeder dua kali tidak boleh menggandakan data.');
    }
}
