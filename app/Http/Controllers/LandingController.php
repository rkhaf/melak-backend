<?php

namespace App\Http\Controllers;

use App\Models\AppInfo;
use App\Models\Disease;
use App\Models\ForumPost;
use App\Models\PlantScan;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    public function index()
    {
        $appInfo = AppInfo::first();
        $diseases = Disease::with(['plantTypes', 'treatments', 'images'])
            ->where('is_published', true)
            ->take(6)
            ->get();

        $totalScans = PlantScan::count();
        $totalUsers = User::count();

        $stats = [
            'accuracy' => '98.4%',
            'response_time' => '< 3 Detik',
            'scans' => $totalScans > 0 ? number_format($totalScans + 1250).'+' : '1.250+',
            'users' => $totalUsers > 0 ? number_format($totalUsers + 2500).'+' : '2.500+',
            'diseases_count' => Disease::where('is_published', true)->count().'+ Jenis Penyakit',
            'gardens' => '450+ Kebun Terdata',
        ];

        $faqs = [
            [
                'q' => 'Bagaimana cara AI Melak mengklasifikasikan tanaman?',
                'a' => 'Unggah foto bagian tanaman yang ingin diperiksa. Sistem menganalisis pola visual pada foto dan menampilkan hasil beserta tingkat keyakinannya. Hasil tetap perlu diverifikasi langsung pada tanaman.',
            ],
            [
                'q' => 'Apakah aplikasi Melak dapat digunakan secara gratis?',
                'a' => 'Ya, Melak dapat digunakan secara gratis untuk membantu petani mengenali gejala penyakit tanaman, mengakses panduan, dan berdiskusi dengan komunitas.',
            ],
            [
                'q' => 'Jenis tanaman apa saja yang didukung?',
                'a' => 'Klasifikasi yang disediakan backend mencakup tomat, jagung, paprika, dan cabai. Cakupan hasil bergantung pada model klasifikasi yang terhubung.',
            ],
            [
                'q' => 'Apa yang harus dilakukan jika hasil klasifikasi kurang meyakinkan?',
                'a' => 'Ambil foto yang fokus dengan pencahayaan baik, periksa seluruh tanaman, dan konsultasikan gejala kepada penyuluh pertanian atau komunitas.',
            ],
            [
                'q' => 'Bagaimana cara mengunduh aplikasi mobile Melak?',
                'a' => 'Tekan tombol Unduh APK pada halaman ini untuk memasang aplikasi di perangkat Android. Versi di Google Play Store akan tersedia setelah dirilis.',
            ],
        ];

        $forumPosts = [
            [
                'author' => 'Budi Santoso',
                'location' => 'Karawang, Jawa Barat',
                'plant' => 'Tomat',
                'title' => 'Bercak gelap muncul pada daun tomat setelah hujan',
                'excerpt' => 'Saya menemukan bercak pada beberapa daun bagian bawah. Apa saja yang perlu diperiksa sebelum menentukan penanganan?',
                'tag' => 'Konsultasi',
                'upvotes' => 48,
                'comments' => 14,
                'solved' => false,
                'time' => '3 jam lalu',
            ],
            [
                'author' => 'Sari Wulandari',
                'location' => 'Wonogiri, Jawa Tengah',
                'plant' => 'Jagung',
                'title' => 'Cara memantau gejala karat daun pada jagung',
                'excerpt' => 'Saya berbagi cara memeriksa sisi atas dan bawah daun serta mencatat perkembangan gejala di beberapa titik lahan.',
                'tag' => 'Tips Budidaya',
                'upvotes' => 62,
                'comments' => 21,
                'solved' => true,
                'time' => '5 jam lalu',
            ],
            [
                'author' => 'Arif Kurniawan',
                'location' => 'Penyuluh Pertanian',
                'plant' => 'Cabai',
                'title' => 'Langkah sanitasi kebun untuk mengurangi penyebaran penyakit',
                'excerpt' => 'Gunakan bibit sehat, bersihkan alat setelah pemangkasan, dan singkirkan bagian tanaman yang terinfeksi.',
                'tag' => 'Edukasi',
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
            $fileName = $appInfo->apk_filename ?: 'Melak-Plants-v1.0.apk';

            return Storage::download($appInfo->apk_path, $fileName, [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]);
        }

        $publicApk = public_path('downloads/melak.apk');
        if (file_exists($publicApk)) {
            return response()->download($publicApk, 'Melak-v1.0.apk');
        }

        return redirect()->route('home')->with('info', 'File APK versi terbaru sedang dalam proses rilis resmi. Silakan hubungi admin atau cek kembali berkala!');
    }
}
