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
                'question' => 'Bagaimana cara AI Melak mendeteksi penyakit ikan?',
                'answer' => 'Melak menggunakan model Convolutional Neural Network (CNN) berbasis Vision AI yang dilatih dengan ribuan dataset klinis penyakit ikan air tawar di Indonesia. Anda cukup memotret ikan yang bergejala sakit, lalu sistem menganalisis lesi, warna luka, bintik, dan kondisi sirip dalam hitungan detik.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Apakah aplikasi Melak dapat digunakan secara gratis?',
                'answer' => 'Ya! Melak dapat diunduh dan digunakan secara gratis oleh seluruh pembudidaya ikan, pemilik tambak, dan akademisi perikanan. Fitur diagnosis dasar, ensiklopedia penyakit, dan forum komunitas terbuka untuk semua pengguna.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Jenis ikan apa saja yang didukung oleh sistem scan Melak?',
                'answer' => 'Saat ini Melak dioptimalkan untuk komoditas utama air tawar nasional: Ikan Lele, Ikan Nila, Ikan Gurame, Ikan Patin, dan Ikan Mas. Model ML kami terus diperbarui dengan tambahan spesies lain.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Bagaimana jika hasil scan memiliki tingkat kepastian (confidence) rendah?',
                'answer' => 'Jika foto kurang fokus, buram, atau penyakit tidak umum, Melak memberi peringatan confidence score rendah dan menyediakan tombol satu-klik "Tanya Komunitas". Foto dan gejala ikan Anda otomatis diformat ke forum agar dibantu sesama pembudidaya dan ahli.',
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
                'answer' => 'Selain scan penyakit, Melak menyediakan ensiklopedia penyakit ikan lengkap, forum komunitas tambak, peta sebaran wabah per provinsi, manajemen kolam dengan skor kesehatan, notifikasi pintar, dan tips harian budidaya.',
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
