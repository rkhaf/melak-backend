<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\HelpContact;
use Illuminate\Database\Seeder;

class HelpSeeder extends Seeder
{
    public function run(): void
    {
        // ── Help Contacts ──────────────────────────────────────────────────────
        HelpContact::truncate();

        HelpContact::insert([
            [
                'type' => 'whatsapp',
                'title' => 'WhatsApp',
                'value' => '0812-3456-7890',
                'icon' => 'whatsapp',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'email',
                'title' => 'Email',
                'value' => 'support@melak.id',
                'icon' => 'email',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'website',
                'title' => 'Website Resmi',
                'value' => 'melak.id',
                'icon' => 'website',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ── FAQs ───────────────────────────────────────────────────────────────
        Faq::truncate();

        Faq::insert([
            [
                'question' => 'Bagaimana cara AI Melak mendeteksi penyakit tanaman?',
                'answer' => 'Melak menggunakan model Vision AI untuk mengenali pola gejala pada tanaman. Potret bagian tanaman yang menunjukkan gejala; sistem akan menganalisis pola bercak, perubahan warna, dan kerusakan yang terlihat.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Apakah aplikasi Melak dapat digunakan secara gratis?',
                'answer' => 'Ya! Melak dapat diunduh dan digunakan secara gratis oleh petani, pemilik kebun, dan akademisi. Fitur diagnosis dasar, ensiklopedia penyakit, dan forum komunitas terbuka untuk semua pengguna.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Jenis tanaman apa saja yang didukung oleh sistem scan Melak?',
                'answer' => 'Klasifikasi tanaman yang tersedia mencakup tomat, jagung, paprika, dan cabai. Cakupan dapat berbeda sesuai model klasifikasi yang terhubung.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Bagaimana jika hasil scan memiliki tingkat kepastian (confidence) rendah?',
                'answer' => 'Jika foto kurang fokus atau gejala tidak dikenali, Melak memberi peringatan tingkat keyakinan rendah. Periksa tanaman secara langsung dan konsultasikan hasilnya dengan penyuluh atau komunitas.',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Bagaimana cara mengunduh aplikasi mobile Melak?',
                'answer' => 'Tekan tombol "Unduh APK" di halaman utama Melak untuk memasang aplikasi di smartphone Android Anda. Versi resmi di Google Play Store juga akan segera tersedia.',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Apa saja fitur lain di dalam aplikasi Melak?',
                'answer' => 'Selain klasifikasi tanaman, Melak menyediakan ensiklopedia penyakit, forum komunitas, peta sebaran, manajemen kebun, notifikasi, dan tips harian budidaya.',
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
