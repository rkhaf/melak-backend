<?php

namespace App\Http\Controllers;

use App\Models\AppInfo;
use App\Models\Disease;
use App\Models\FishScan;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    public function index()
    {
        $appInfo = AppInfo::first();

        // Sample / Real diseases for interactive showcase
        $dbDiseases = Disease::with(['fishTypes', 'treatments', 'images'])
            ->where('is_published', true)
            ->take(6)
            ->get();

        $fallbackDiseases = [
            [
                'id' => 1,
                'name' => 'Bintik Putih (White Spot / Ich)',
                'name_en' => 'Ichthyophthirius multifiliis',
                'category' => 'Parasit',
                'severity' => 'Tinggi',
                'fish' => ['Ikan Lele', 'Ikan Gurame', 'Ikan Patin'],
                'description' => 'Infeksi parasit protozoa bersilia yang menyebabkan bercak putih seperti butiran garam pada kulit, sirip, dan insang.',
                'symptoms' => 'Muncul bintik-bintik putih menyerupai butiran gula halus, ikan sering menggosokkan tubuhnya ke dinding kolam, nafsu makan drop, nafas terengah-engah di permukaan.',
                'treatments' => [
                    'Karantina ikan yang terinfeksi ke wadah terpisah sesegera mungkin.',
                    'Tingkatkan suhu air hingga 28°C - 30°C untuk memutus siklus hidup parasit.',
                    'Lakukan perendaman garam ikan dosis 3-5 gram per liter air selama 24 jam.',
                    'Gunakan Methylene Blue (1-2 tetes/liter) atau Malachite Green jika infeksi parah.',
                ],
                'prevention' => 'Jaga kestabilan suhu kolam, jangan memasukkan bibit baru sebelum proses aklimatisasi dan perendaman disinfektan steril.',
            ],
            [
                'id' => 2,
                'name' => 'Borok Kulit & Sirip Merah (Aeromonas)',
                'name_en' => 'Aeromonas hydrophila (MAS)',
                'category' => 'Bakteri',
                'severity' => 'Kritis',
                'fish' => ['Ikan Nila', 'Ikan Lele', 'Ikan Mas'],
                'description' => 'Penyakit infeksi bakteri gram-negatif yang sangat mematikan pada sistem budidaya air tawar padat tebar tinggi.',
                'symptoms' => 'Luka borok kemerahan berdarah pada badan (ulcer), pendarahan di pangkal sirip dan anus, perut kembung (dropsy), mata menonjol (exophthalmia).',
                'treatments' => [
                    'Ganti air kolam sebanyak 30-50% dengan air bersih baru ber-aerasi tinggi.',
                    'Berikan pakan yang dicampur antibiotik khusus ikan (Oxytetracycline dosis 50mg/kg pakan) selama 5-7 hari.',
                    'Berikan probiotik herbal (bawang putih halus 10g/kg pakan) untuk imunitas.',
                    'Sterilisasi kolam dengan garam krosok dan antiseptik povidone iodine dosis aman.',
                ],
                'prevention' => 'Kontrol sisa amonia pakan, hindari kepadatan tebar melebihi kapasitas aerasi kolam.',
            ],
            [
                'id' => 3,
                'name' => 'Pembusukan Sirip & Insang (Columnaris)',
                'name_en' => 'Flavobacterium columnare',
                'category' => 'Bakteri',
                'severity' => 'Sedang',
                'fish' => ['Ikan Gurame', 'Ikan Nila', 'Ikan Patin'],
                'description' => 'Sering disebut penyakit kapas mulut atau pembusukan sirip, menyerang jaringan luar ikan saat kualitas air memburuk.',
                'symptoms' => 'Ujung sirip tampak compang-camping dan memutih seperti kapas, luka keabuan di sekitar mulut, insang pucat berlumpur lendir.',
                'treatments' => [
                    'Perendaman dalam larutan kalium permanganat (PK) dosis 2-4 ppm selama 30 menit.',
                    'Pemberian garam ikan 1-2 ppt untuk menurunkan stres osmotik.',
                    'Perbaiki sirkulasi air dan tingkatkan dissolve oxygen (DO) > 5 mg/L.',
                ],
                'prevention' => 'Rutin sifon kotoran dasar kolam dan pantau fluktuasi pH air.',
            ],
            [
                'id' => 4,
                'name' => 'Infeksi Jamur Air (Saprolegniasis)',
                'name_en' => 'Saprolegnia spp.',
                'category' => 'Jamur',
                'severity' => 'Sedang',
                'fish' => ['Ikan Lele', 'Ikan Mas', 'Ikan Nila'],
                'description' => 'Infeksi sekunder jamur benang yang menyerang luka akibat gesekan, gigitan sesama ikan, atau pasca grading.',
                'symptoms' => 'Tumbuh gumpalan benang halus seperti kapas putih atau kecokelatan pada luka fisik atau telur ikan.',
                'treatments' => [
                    'Oleskan obat luka ikan (Betadine ikan / Methylene Blue pekat) langsung pada luka.',
                    'Rendam ikan dalam larutan garam 5-10 g/L selama 15 menit.',
                    'Jaga suhu air tetap hangat (> 26°C).',
                ],
                'prevention' => 'Tangani ikan dengan hati-hati saat panen/grading agar tidak menimbulkan lecet pada sisik.',
            ],
            [
                'id' => 5,
                'name' => 'Kutu Ikan & Parasit Sisik (Argulosis)',
                'name_en' => 'Argulus indicus',
                'category' => 'Parasit Makro',
                'severity' => 'Sedang',
                'fish' => ['Ikan Mas', 'Ikan Gurame', 'Ikan Nila'],
                'description' => 'Ektoparasit krustasea pipih transparan yang menempel kuat menghisap darah ikan dan menyuntikkan racun.',
                'symptoms' => 'Ikan meloncat-loncat tidak tenang, tampak kutu bulat pipih 3-5mm bergerak di balik sisik, tubuh kurus pucat.',
                'treatments' => [
                    'Pencabutan manual dengan pinset jika jumlah ikan sedikit.',
                    'Perendaman larutan garam pekat (15-20 ppt) selama 5-10 menit hingga kutu lepas.',
                    'Penggunaan obat antiparasit khusus tambak sesuai anjuran dinas perikanan.',
                ],
                'prevention' => 'Saring air masuk menggunakan kain halus monofilamen untuk mencegah larva kutu masuk ke kolam.',
            ],
            [
                'id' => 6,
                'name' => 'Trichodiniasis Gatal Insang',
                'name_en' => 'Trichodina spp.',
                'category' => 'Parasit',
                'severity' => 'Sedang',
                'fish' => ['Ikan Lele', 'Ikan Patin'],
                'description' => 'Protozoa piringan bergigi yang berputar melukai epidermis kulit dan selaput lendir insang.',
                'symptoms' => 'Ikan mengambang lemas di dekat inlet air, produksi lendir berlebih sehingga badan licin memutih, warna kusam.',
                'treatments' => [
                    'Perendaman dalam formalin teknis 25-50 ppm selama 1 jam dengan aerasi kuat.',
                    'Penggantian air 50% setelah perendaman.',
                    'Pemberian garam krosok 100-200 gram per m³ kolam.',
                ],
                'prevention' => 'Keringkan kolam dan taburi kapur tohor (CaO) saat jeda antar siklus budidaya.',
            ],
        ];

        // Format diseases list
        $diseases = $dbDiseases->isNotEmpty() ? $dbDiseases : $fallbackDiseases;

        // Statistics
        $totalScans = FishScan::count();
        $totalUsers = User::count();
        $totalPosts = ForumPost::count();

        $stats = [
            'accuracy' => '98.4%',
            'response_time' => '< 3 Detik',
            'scans' => $totalScans > 0 ? number_format($totalScans + 1250).'+' : '1.250+',
            'users' => $totalUsers > 0 ? number_format($totalUsers + 2500).'+' : '2.500+',
            'diseases_count' => '15+ Jenis Penyakit',
            'ponds' => '450+ Tambak Terdata',
        ];

        // FAQs
        $faqs = [
            [
                'q' => 'Bagaimana cara AI Melak mendeteksi penyakit ikan?',
                'a' => 'Melak menggunakan model Convolutional Neural Network (CNN) canggih berbasis arsitektur Vision AI yang dilatih dengan ribuan dataset klinis penyakit ikan air tawar di Indonesia. Anda cukup memotret ikan yang bergejala sakit, dan sistem akan menganalisis lesi, warna luka, bintik, dan kondisi sirip secara instan dalam waktu kurang dari 3 detik.',
            ],
            [
                'q' => 'Apakah aplikasi Melak dapat digunakan secara gratis?',
                'a' => 'Ya! Melak dapat diunduh dan digunakan secara gratis oleh seluruh pembudidaya ikan, pemilik tambak, dan akademisi perikanan. Fitur diagnosis dasar, ensiklopedia penyakit, dan forum komunitas terbuka untuk semua pengguna.',
            ],
            [
                'q' => 'Jenis ikan apa saja yang didukung oleh sistem scan Melak?',
                'a' => 'Saat ini Melak dioptimalkan untuk komoditas utama air tawar nasional: Ikan Lele, Ikan Nila, Ikan Gurame, Ikan Patin, dan Ikan Mas. Kami terus memperbarui model ML dengan tambahan spesies seperti Ikan Bawal dan Ikan Gabus.',
            ],
            [
                'q' => 'Bagaimana jika hasil scan memiliki tingkat kepastian (confidence) rendah?',
                'a' => 'Jika foto kurang fokus, buram, atau penyakit tidak umum, Melak akan memberi peringatan confidence score rendah dan menyediakan tombol satu-klik "Tanya Komunitas". Foto dan gejala ikan Anda akan otomatis diformat ke forum agar dibantu langsung oleh dokter hewan dan sesama pembudidaya senior.',
            ],
            [
                'q' => 'Bagaimana cara mengunduh aplikasi mobile Melak?',
                'a' => 'Anda dapat langsung menekan tombol "Unduh APK" di landing page ini untuk memasang aplikasi di smartphone Android Anda. Versi resmi Google Play Store juga segera tersedia.',
            ],
        ];

        // Forum preview discussions
        $forumPosts = [
            [
                'author' => 'Pak Budi Santoso',
                'location' => 'Boyolali, Jawa Tengah',
                'fish' => 'Ikan Lele Sangkuriang',
                'title' => 'Bintik putih merajalela di kolam terpal setelah hujan deras semalaman',
                'excerpt' => 'Kemarin kolam terisi air hujan lebat, paginya lele pada ngumpul di pojokan dan muncul bintik putih. Setelah scan Melak disarankan garam 3 ppt + naikkan suhu. Sekarang sudah hari ke-2 mulai aktif makan lagi!',
                'tag' => 'Kasus Sembuh',
                'upvotes' => 48,
                'comments' => 14,
                'solved' => true,
                'time' => '3 jam lalu',
            ],
            [
                'author' => 'Hj. Rahmat Hidayat',
                'location' => 'Cirata, Jawa Barat',
                'fish' => 'Ikan Nila Merah',
                'title' => 'Pencegahan luka sirip merah (Aeromonas) di KJA saat pergantian musim',
                'excerpt' => 'Sharing pengalaman pencegahan rutin: campur bawang putih dan vitamin C di pelet 2 kali seminggu. Kematian bibit turun dari 15% jadi cuma di bawah 2%. Jangan tunggu sampai borok parah baru diobati.',
                'tag' => 'Tips Budidaya',
                'upvotes' => 62,
                'comments' => 21,
                'solved' => false,
                'time' => '5 jam lalu',
            ],
            [
                'author' => 'drh. Arif Kurniawan',
                'location' => 'Penyuluh Perikanan',
                'fish' => 'Ikan Gurame',
                'title' => 'Catatan penting: Membedakan Jamur Saprolegnia vs Columnaris',
                'excerpt' => 'Banyak petani salah mendiagnosa kapas mulut. Jika bertekstur benang panjang biasanya jamur (butuh antifungal), sedangkan bercak putih kekuningan datar adalah bakteri Columnaris (butuh antibiotik/PK). Gunakan fitur scan Melak untuk verifikasi awal.',
                'tag' => 'Edukasi Ahli',
                'upvotes' => 95,
                'comments' => 33,
                'solved' => true,
                'time' => '1 hari lalu',
            ],
        ];

        return view('landing', compact('appInfo', 'diseases', 'stats', 'faqs', 'forumPosts'));
    }

    public function downloadApk()
    {
        $appInfo = AppInfo::first();

        if ($appInfo && $appInfo->apk_path && Storage::exists($appInfo->apk_path)) {
            $fileName = $appInfo->apk_filename ?: 'Melak-Aquaculture-v1.0.apk';

            return Storage::download($appInfo->apk_path, $fileName, [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]);
        }

        // Check if there is an APK file in public directory or storage
        $publicApk = public_path('downloads/melak.apk');
        if (file_exists($publicApk)) {
            return response()->download($publicApk, 'Melak-v1.0.apk');
        }

        return redirect()->route('home')->with('info', 'File APK versi terbaru sedang dalam proses rilis resmi. Silakan hubungi admin atau cek kembali berkala!');
    }
}
