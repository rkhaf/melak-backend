<?php

namespace Database\Seeders;

use App\Models\Disease;
use Illuminate\Database\Seeder;

/**
 * 11 kelas output model ViT → tabel `diseases` (FR-10.11).
 *
 * Urutan & slug array ini HARUS identik dengan ml-service/label_map.json —
 * dicek oleh DiseaseSeederTest. Field disease_id di label_map diisi lewat
 * `php artisan ml:sync-label-map`.
 *
 * Sengaja tidak memakai truncate(): id harus stabil karena sudah dirujuk
 * label_map.json dan scan_results.disease_id (juga editan admin tidak hilang).
 * Jalankan seberapa pun kali pun hasilnya sama (idempotent).
 */
class DiseaseSeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private const DISEASES = [
        [
            'slug' => 'bacterial-red-disease',
            'name' => 'Penyakit Bakteri Red Disease',
            'name_en' => 'Bacterial Red Disease',
            'model_label' => 'Fish_Bacterial Red disease',
            'category' => 'bacteria',
            'severity' => 'berat',
            'description' => 'Penyakit bakteri yang menyerang jaringan darah ikan sehingga tubuh, pangkal sirip, dan anus tampak memerah akibat perdarahan subkutan. Menular cepat pada kolam padat tebar dan dapat menimbulkan kematian massal bila penanganan tertunda.',
            'causes' => 'Infeksi bakteri gram-negatif (Aeromonas, Pseudomonas, Vibrio) yang berkembang biak ketika kualitas air menurun, kepadatan tebar tinggi, adanya luka mekanik saat pengangkatan, serta stres akibat perubahan suhu mendadak.',
            'symptoms' => "Kemerahan pada tubuh, pangkal sirip, dan anus\nPerdarahan pada pangkal sirip dan ekor\nSirip menempel ke badan (clamped) dan berenang tidak stabil\nNafsu makan turun, ikan sering menyendiri di dasar kolam\nInsang pucat disertai lendir berlebih",
            'recovery_days_min' => 5,
            'recovery_days_max' => 14,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin', 'Mas'],
            'treatments' => [
                ['title' => 'Pisahkan ikan yang terinfeksi', 'description' => 'Pindahkan ikan bergejala ke kolam karantina dengan air dari kolam asal agar ikan tidak stres karena perubahan media mendadak.', 'duration_days' => 1],
                ['title' => 'Perbaiki kualitas air kolam', 'description' => 'Ganti 30–50% air, naikkan aerasi, dan turunkan kepadatan tebar. Uji pH (6,5–8,0), amonia, dan nitrit sebelum ikan dikembalikan.', 'duration_days' => 3],
                ['title' => 'Pemberian antibiotik melalui pakan', 'description' => 'Beri pakan yang dicampur antibiotik oksitetrasiklin 50–75 mg per kg pakan selama 5–7 hari. Berikan pakan setengah porsi agar habis dalam 15 menit.', 'medicines' => [
                    ['name' => 'Oksitetrasiklin', 'dose' => '50–75 mg per kg pakan', 'frequency' => '1× sehari', 'notes' => 'Hentikan 7 hari sebelum panen'],
                    ['name' => 'Garam ikan', 'dose' => '3 ppt', 'frequency' => '1× sehari', 'notes' => 'Rendam 20 menit, hentikan bila ikan stres'],
                ], 'duration_days' => 7],
            ],
            'preventions' => [
                'Karantina ikan baru minimal 7 hari sebelum dimasukkan ke kolam utama',
                'Jaga kepadatan tebar dan pastikan aerasi berjalan 24 jam',
                'Angkat ikan mati segera dan jangan gunakan jaring yang sama antar kolam',
            ],
        ],
        [
            'slug' => 'aeromoniasis',
            'name' => 'Infeksi Bakteri Aeromonas (Aeromoniasis)',
            'name_en' => 'Aeromoniasis',
            'model_label' => 'Fish_Bacterial diseases - Aeromoniasis',
            'category' => 'bacteria',
            'severity' => 'berat',
            'description' => 'Infeksi bakteri Aeromonas hydrophila yang menyerang kulit dan jaringan dalam, menimbulkan luka borok terbuka (ulcer) hingga perdarahan. Merupakan salah satu penyebab kematian budidaya air tawar paling besar di Indonesia.',
            'causes' => 'Bakteri Aeromonas hidup alami di air dan menjadi patogen saat ikan stres, kualitas air buruk, luka akibat padat tebar, atau pakan berkualitas rendah. Suhu 22–28 °C mempercepat pertumbuhannya.',
            'symptoms' => "Luka borok kemerahan berdarah pada badan (ulcer)\nPendarahan di pangkal sirip dan anus\nPerut kembung berisi cairan (dropsy)\nMata menonjol (exophthalmia) dan sisik terangkat\nIkan mengambang lemas di permukaan",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin', 'Mas'],
            'treatments' => [
                ['title' => 'Karantina dan isolasi luka', 'description' => 'Pisahkan ikan sakit. Bersihkan luka borok dengan kapas yang dicelup larutan povidone iodine 10% sebelum ikan dikarantina.', 'duration_days' => 2],
                ['title' => 'Rawat luka dan stabilkan air', 'description' => 'Rendam ikan dalam larutan garam 3 ppt selama 20 menit, dua kali sehari. Jaga suhu 25–28 °C dan naikkan aerasi.', 'duration_days' => 5],
                ['title' => 'Antibiotik oral 5–7 hari', 'description' => 'Gunakan antibiotik yang sudah lolos uji (oksitetrasiklin atau asam oksolinat) sesuai dosis anjuran. Catat tanggal pemberian untuk jeda panen.', 'medicines' => [
                    ['name' => 'Asam oksolinat', 'dose' => '10 mg per kg pakan', 'frequency' => '1× sehari', 'notes' => 'Jangan digabung dengan pakan berkapur'],
                    ['name' => 'Garam ikan', 'dose' => '3 ppt', 'frequency' => '2× sehari', 'notes' => 'Rendam 20 menit per sesi'],
                ], 'duration_days' => 7],
            ],
            'preventions' => [
                'Turunkan stres penanganan dengan menggunakan jaring berbahan lembut',
                'Bekukan atau karantina pakan sebelum diberikan agar tidak membawa patogen',
                'Lakukan penggantian air bertahap 20–30% setiap 3 hari',
            ],
        ],
        [
            'slug' => 'bacterial-gill-disease',
            'name' => 'Penyakit Bakteri Insang',
            'name_en' => 'Bacterial Gill Disease',
            'model_label' => 'Fish_Bacterial gill disease',
            'category' => 'bacteria',
            'severity' => 'sedang',
            'description' => 'Peradangan bakteri pada insang yang merusak jaringan penerima oksigen. Ikan kesulitan bernapas dan tumbuh lambat; bila dibiarkan, kematian terjadi akibat sesak napas.',
            'causes' => 'Bakteri Flavobacterium danFlexibacter menempel pada insang ketika kepadatan tinggi, aliran air lemah, dan kandungan amonia meningkat. Debu organik di air memperburuk kondisi.',
            'symptoms' => "Insang pucat hingga keabuan dan berlendir tebal\nUjung helai insang menempel atau nekrosis\nNapas cepat dan sering menyembul di permukaan\nInsang tertutup lendir saat diperiksa\nIkan menolak makan dan berenang mengambang",
            'recovery_days_min' => 5,
            'recovery_days_max' => 14,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin'],
            'treatments' => [
                ['title' => 'Tingkatkan sirkulasi dan aerasi', 'description' => 'Naikkan debit air masuk dan bersihkan saringan filter dari endapan organik agar insang mendapat air bersih.', 'duration_days' => 3],
                ['title' => 'Rendam dengan kalium permanganat', 'description' => 'Rendam ikan dalam larutan kalium permanganat 2–4 ppm selama 20–30 menit untuk mengangkat lendir pada insang. Hindari kontak dengan logam.', 'duration_days' => 2],
                ['title' => 'Turunkan kepadatan tebar', 'description' => 'Pindahkan sebagian ikan ke kolam lain dan kurangi porsi pakan sementara untuk menurunkan beban metabolisme air.', 'duration_days' => 5],
            ],
            'preventions' => [
                'Bersihkan filter dan dasar kolam secara rutin dari sisa pakan',
                'Pastikan kepadatan tebar sesuai kapasitas kolam',
                'Hindari perubahan suhu air lebih dari 2 °C dalam sehari',
            ],
        ],
        [
            'slug' => 'saprolegniasis',
            'name' => 'Saprolegniasis (Jamur Air)',
            'name_en' => 'Saprolegniasis',
            'model_label' => 'Fish_Fungal diseases Saprolegniasis',
            'category' => 'fungi',
            'severity' => 'sedang',
            'description' => 'Infeksi jamur air Saprolegnia yang tumbuh pada luka terbuka, sirip, dan telur. Ditandai gumpalan menyerupai kapas putih yang menyebar bila tidak diobati.',
            'causes' => 'Jamur opportunistik masuk melalui luka fisik, telur yang gagal fertil, atau ikan yang lemah setelah transportasi. Suhu rendah dan air berdebu mempercepat pertumbuhan.',
            'symptoms' => "Tumbuh gumpalan benang halus seperti kapas putih atau kecokelatan\nJamur menempel pada luka, sirip, atau telur\nArea di bawah gumpalan mengalami peradangan\nIkan lesu dan nafsu makan menurun\nTelur menetas tidak normal pada kolam pendederan",
            'recovery_days_min' => 4,
            'recovery_days_max' => 10,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin', 'Mas'],
            'treatments' => [
                ['title' => 'Pisahkan dan keringkan luka sejenak', 'description' => 'Pindahkan ikan ke kolam bersih. Keluarkan ikan sebentar dan oleskan larutan malachite green 0,1 ppm pada bagian yang terkena jamur.', 'duration_days' => 1],
                ['title' => 'Naikkan suhu air bertahap', 'description' => 'Tingkatkan suhu ke 28–30 °C dengan kenaikan maksimal 1 °C per hari untuk menghambat pertumbuhan jamur.', 'duration_days' => 3],
                ['title' => 'Rendam garam dan antijamur', 'description' => 'Rendam ikan dalam garam 3 ppt selama 15–20 menit, dua kali sehari. Untuk wabah pada telur, gunakan malachite green sesuai dosis izin.', 'medicines' => [
                    ['name' => 'Garam ikan', 'dose' => '3 ppt', 'frequency' => '2× sehari', 'notes' => 'Rendam 15–20 menit'],
                    ['name' => 'Malachite green', 'dose' => '0,1 ppm', 'frequency' => '1× sehari, 2 hari', 'notes' => 'Hindari sinar matahari langsung'],
                ], 'duration_days' => 5],
            ],
            'preventions' => [
                'Buang telur mati dari kolam pendederan setiap hari',
                'Karantina ikan baru dan pastikan tidak ada luka terbuka saat ditebar',
                'Jaga kualitas air dan hindari kontaminasi pakan berjamur',
            ],
        ],
        [
            'slug' => 'ikan-sehat',
            'name' => 'Ikan Sehat',
            'name_en' => 'Healthy Fish',
            'model_label' => 'Fish_Healthy Fish',
            'category' => 'other',
            'severity' => 'ringan',
            'description' => 'Model tidak menemukan tanda penyakit pada foto. Organ tubuh utuh, warna normal, dan perilaku ikan sesuai kondisi sehat. Tetap lakukan pemantauan rutin karena deteksi dini bergantung pada kualitas foto.',
            'causes' => 'Kondisi sehat — tidak ada penyebab penyakit. Prediksi ini juga bisa muncul bila foto kurang jelas sehingga gejala tidak terbaca.',
            'symptoms' => "Seluruh tubuh utuh tanpa luka atau bintik\nSirip terbuka penuh dan tidak menempel ke badan\nWarna cerah dan selaput lendir normal\nNafsu makan aktif dan responsif terhadap pakan\nTidak ada lendir berlebih pada tubuh dan insang",
            'recovery_days_min' => null,
            'recovery_days_max' => null,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin', 'Mas'],
            'treatments' => [],
            'preventions' => [
                'Lakukan pemeriksaan rutin minimal sekali sehari',
                'Jaga kualitas air dengan uji pH, amonia, dan nitrit secara berkala',
                'Catat pertumbuhan dan nafsu makan sebagai pembanding kondisi normal',
            ],
        ],
        [
            'slug' => 'penyakit-parasit',
            'name' => 'Penyakit Parasit',
            'name_en' => 'Parasitic Disease',
            'model_label' => 'Fish_Parasitic diseases',
            'category' => 'parasite',
            'severity' => 'sedang',
            'description' => 'Infeksi ektoparasit seperti Trichodina, Dactylogyrus (kutu daun), Argulus (kutu air), dan kutu ikan yang menempel pada kulit serta insang. Menular antar ikan melalui kontak dan peralatan.',
            'causes' => 'Parasit masuk bersama ikan baru yang tidak dikarantina, menyebar akibat kepadatan tinggi, dan berkembang ketika ikan mengalami stres serta kualitas air menurun.',
            'symptoms' => "Ikan sering menggosokkan tubuh ke dinding atau dasar kolam\nProduksi lendir berlebih sehingga badan licin memutih\nKutu bulat pipih 3–5 mm terlihat bergerak di balik sisik\nSirip compang-camping dan tubuh tampak kurus\nInsang pucat berlumpur dan pernapasan tersendat",
            'recovery_days_min' => 5,
            'recovery_days_max' => 12,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin', 'Mas'],
            'treatments' => [
                ['title' => 'Rendam larutan garam', 'description' => 'Rendam ikan dalam garam 3–5 ppt selama 20–30 menit untuk melepaskan parasit dari kulit. Ulangi setiap 3 hari sampai gejala hilang.', 'duration_days' => 5],
                ['title' => 'Kuras dan saring kolam', 'description' => 'Kuras bagian besar air dan saring dengan jaring halus untuk menangkap parasit yang terlepas. Bersihkan peralatan dengan desinfektan.', 'duration_days' => 2],
                ['title' => 'Aplikasi pengusir parasit', 'description' => 'Untuk infeksi berat, gunakan insektisida nabati atau obat antiparasit sesuai dosis anjuran pada air kolam.', 'medicines' => [
                    ['name' => 'Garam ikan', 'dose' => '3–5 ppt', 'frequency' => '2× sehari', 'notes' => 'Rendam 20–30 menit'],
                    ['name' => 'Kalium permanganat', 'dose' => '2 ppm', 'frequency' => '1× sehari', 'notes' => 'Jangan digabung dengan garam'],
                ], 'duration_days' => 7],
            ],
            'preventions' => [
                'Karantina ikan baru minimal 7 hari sebelum tebar',
                'Desinfeksi jaring, ember, dan alat angkat antar kolam',
                'Jaga kepadatan tebar dan cek insang ikan secara berkala',
            ],
        ],
        [
            'slug' => 'white-tail-disease',
            'name' => 'Penyakit Virus White Tail',
            'name_en' => 'White Tail Disease',
            'model_label' => 'Fish_Viral diseases White tail disease',
            'category' => 'virus',
            'severity' => 'berat',
            'description' => 'Penyakit viral yang menyerang jaringan otot dan menimbulkan perubahan warna putih pada ujung ekor. Tidak ada obat antivirus untuk ikan, sehingga penanganan berfokus pada biosekuriti dan pemulihan kondisi air.',
            'causes' => 'Infeksi virus (mirip nodavirus/mRNAV) yang menyebar melalui kontak langsung, air terkontaminasi, dan pakan hidup yang tidak higienis. Risiko meningkat pada suhu rendah dan kepadatan tinggi.',
            'symptoms' => "Ujung ekor dan sirip memucat hingga putih\nGerakan ikan melambat dan berenang tidak beraturan\nNafsu makan turun drastis hingga menolak pakan\nTubuh kurus meski pakan tersedia\nKematian meningkat tanpa tanda bakteri terlihat",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'fish_types' => ['Lele', 'Nila', 'Gurame', 'Patin'],
            'treatments' => [
                ['title' => 'Isolasi ikan terinfeksi', 'description' => 'Pisahkan ikan bergejala dan jangan menggabungkan air kolam. Gunakan alat khusus untuk kolam karantina.', 'duration_days' => 1],
                ['title' => 'Perketat biosekuriti', 'description' => 'Desinfeksi peralatan dengan larutan povidone iodine 10%, buang ikan mati dengan cara tertutup, dan hentikan pakan hidup sementara.', 'duration_days' => 7],
                ['title' => 'Perbaiki kualitas air dan beri nutrisi', 'description' => 'Jaga amonia dan nitrit mendekati nol, naikkan aerasi, serta beri pakan berkualitas tinggi mengandung vitamin C untuk mendukung imunitas.', 'duration_days' => 14],
            ],
            'preventions' => [
                'Terapkan biosekuriti ketat pada setiap pintu masuk kolam',
                'Jangan memasukkan ikan dari kolam dengan riwayat kematian misterius',
                'Pisahkan dan buang ikan mati dalam 24 jam untuk memutus rantai penularan',
            ],
        ],
        [
            'slug' => 'shrimp-black-gill',
            'name' => 'Udang Black Gill',
            'name_en' => 'Shrimp Black Gill',
            'model_label' => 'Shrimp_Black_Gill',
            'category' => 'bacteria',
            'severity' => 'sedang',
            'description' => 'Kondisi di mana insang udang berubah hitam akibat infeksi bakteri atau penumpukan pigment melanin akibat stres lingkungan. Mengganggu pernapasan dan pertumbuhan, serta menurunkan kualitas panen.',
            'causes' => 'Didominasi bakteri Vibrio pada air dengan salinitas tidak stabil, kepadatan tinggi, dan kualitas air (pH, amonia, oksigen terlarut) yang turun. Sering muncul bersama tahap ganti cangkang.',
            'symptoms' => "Insang udang berwarna hitam pekat\nUdang berenang lebih banyak di permukaan\nCangkang keras dan sulit berganti kulit\nPertumbuhan tidak seragam antar individu\nNafsu makan menurun dan sisa pakan menumpuk",
            'recovery_days_min' => 5,
            'recovery_days_max' => 14,
            'fish_types' => ['Udang Vaname', 'Udang Galah'],
            'treatments' => [
                ['title' => 'Stabilkan kualitas air tambak', 'description' => 'Uji dan sesuaikan salinitas, pH (7,5–8,5), dan oksigen terlarut (≥4 mg/L). Nyalakan kipas dan blower sepanjang malam.', 'duration_days' => 5],
                ['title' => 'Terapkan probiotik dan kurangi pakan', 'description' => 'Tambahkan probiotik untuk menekan bakteri patogen dan kurangi pakan 20–30% selama 3 hari agar sisa pakan tidak menumpuk.', 'duration_days' => 3],
                ['title' => 'Panen parsial untuk tekan kepadatan', 'description' => 'Ambil udang berukuran konsumen untuk menurunkan kepadatan dan memutus penyebaran infeksi ke udang yang masih tumbuh.', 'duration_days' => 1],
            ],
            'preventions' => [
                'Lakukan pengeringan dan pengolahan dasar tambak sebelum tebar',
                'Jaga kepadatan tebar dan siapkan kolam cadangan untuk pemindahan',
                'Uji air setiap hari pada musim hujan dan saat ganti cangkang',
            ],
        ],
        [
            'slug' => 'udang-sehat',
            'name' => 'Udang Sehat',
            'name_en' => 'Healthy Shrimp',
            'model_label' => 'Shrimp_Healthy',
            'category' => 'other',
            'severity' => 'ringan',
            'description' => 'Model tidak menemukan tanda penyakit pada udang. Insang berwarna normal, cangkang utuh, dan perilaku aktif. Pertahankan pemantauan harian karena sebagian penyakit baru terlihat setelah ganti cangkang.',
            'causes' => 'Kondisi sehat — tidak ada penyebab penyakit. Prediksi ini juga bisa muncul bila sudut foto tidak menangkap insang dengan jelas.',
            'symptoms' => "Insang berwarna normal, tidak hitam atau putih\nCangkang utuh dan proses ganti kulit berjalan normal\nBergerak aktif dan merespons pakan\nTubuh transparan hingga kecokelatan tanpa bintik\nTidak ada luka atau bagian anggota tubuh yang hilang",
            'recovery_days_min' => null,
            'recovery_days_max' => null,
            'fish_types' => ['Udang Vaname', 'Udang Galah'],
            'treatments' => [],
            'preventions' => [
                'Pantau nafsu makan dan sisa pakan setiap hari',
                'Uji kualitas air minimal dua kali sehari pada fase kritis',
                'Catat jumlah udang berganti cangkang sebagai indikator pertumbuhan',
            ],
        ],
        [
            'slug' => 'shrimp-white-spot-wssv',
            'name' => 'Udang White Spot (WSSV)',
            'name_en' => 'White Spot Syndrome Virus (WSSV)',
            'model_label' => 'Shrimp_White_Spot_Syndrome_Virus',
            'category' => 'virus',
            'severity' => 'berat',
            'description' => 'Wabah virus WSSV yang menimbulkan bintik putih menyerupai garam pada cangkang, insang, dan otot udang. Menyebar sangat cepat dan dapat menyebabkan kematian total dalam hitungan hari.',
            'causes' => 'Virus White Spot Syndrome yang menyebar melalui udang terinfeksi, air laut terkontaminasi, dan benur dari sumber terpapar. Kebijakan biosekuriti longgar serta perubahan salinitas drastis memicu kejadian.',
            'symptoms' => "Bintik putih menyerupai butiran garam pada cangkang, ekor, dan insang\nUdang berenang di permukaan secara tidak wajar\nWarna tubuh merah muda hingga kemerahan\nCangkang lunak dan mudah terkelupas\nKematian meningkat drastis dalam 2–3 hari",
            'recovery_days_min' => 10,
            'recovery_days_max' => 30,
            'fish_types' => ['Udang Vaname', 'Udang Galah'],
            'treatments' => [
                ['title' => 'Isolasi dan hentikan penyebaran', 'description' => 'Tutup sirkulasi air dari kolam terinfeksi. Jangan memindahkan air, alat, atau udang tanpa desinfeksi.', 'duration_days' => 1],
                ['title' => 'Terapkan tindakan biosekuriti menyeluruh', 'description' => 'Desinfeksi peralatan dengan klorin 20–50 ppm, musnahkan udang mati dengan tertutup, dan larang pengunjung masuk area tambak.', 'duration_days' => 7],
                ['title' => 'Bangun kembali populasi', 'description' => 'Setelah wabah berakhir, keringkan dan olah dasar kolam selama minimal 10 hari sebelum ditebar ulang dengan benur bersertifikat bebas virus.', 'duration_days' => 30],
            ],
            'preventions' => [
                'Gunakan benur bersertifikat bebas WSSV dari hatchery terpercaya',
                'Desinfeksi sepatu, alat, dan kendaraan yang masuk area tambak',
                'Stabilkan salinitas dan hindari perubahan mendadak lebih dari 5 ppt',
            ],
        ],
        [
            'slug' => 'shrimp-wssv-black-gill',
            'name' => 'Udang White Spot + Black Gill',
            'name_en' => 'WSSV with Black Gill',
            'model_label' => 'Shrimp_White_Spot_Syndrome_Virus_and_Black_Gill',
            'category' => 'virus',
            'severity' => 'kritis',
            'description' => 'Infeksi ganda WSSV yang disertai peradangan insang hitam — kondisi paling parah pada budidaya udang. Udang menunjukkan bintik putih sekaligus insang menghitam, dengan tingkat kematian sangat tinggi.',
            'causes' => 'Kombinasi tekanan virus WSSV dan infeksi bakteri sekunder pada kondisi air buruk. Kepadatan tinggi, salinitas tidak stabil, dan biosekuriti lemah menjadi pemicu utama kejadian ini.',
            'symptoms' => "Bintik putih pada cangkang disertai insang hitam\nUdang lemas di dasar dan berenang tidak koordinat\nTubuh kemerahan dengan cangkang lunak\nKonsumsi pakan anjlok hingga berhenti\nKematian massal dalam 48–72 jam",
            'recovery_days_min' => 14,
            'recovery_days_max' => 45,
            'fish_types' => ['Udang Vaname', 'Udang Galah'],
            'treatments' => [
                ['title' => 'Karantina mendesak dan hentikan sirkulasi', 'description' => 'Segel kolam terinfeksi, matikan pompa yang menghubungkan kolam lain, dan laporkan ke penyuluh perikanan setempat.', 'duration_days' => 1],
                ['title' => 'Turunkan kepadatan lewat panen darurat', 'description' => 'Panen udang yang masih layak konsumen secepatnya untuk memperkecil populasi terpapar dan membatasi kerugian.', 'duration_days' => 2],
                ['title' => 'Sanitasi total dan siapkan ulang kolam', 'description' => 'Keringkan kolam, kapur dan olah dasar, lalu pastikan uji laboratorium bebas WSSV sebelum tebar ulang. Ganti selang dan saringan yang terkontaminasi.', 'duration_days' => 45],
            ],
            'preventions' => [
                'Lakukan uji PCR benur sebelum masuk kolam',
                'Terapkan biosekuriti ketat dan batasi kunjungan ke area tambak',
                'Siapkan kolam isolasi sejak awal musim untuk penanganan cepat',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::DISEASES as $data) {
            $disease = Disease::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'name_en' => $data['name_en'],
                    'category' => $data['category'],
                    'severity' => $data['severity'],
                    'description' => $data['description'],
                    'causes' => $data['causes'],
                    'symptoms' => $data['symptoms'],
                    'recovery_days_min' => $data['recovery_days_min'],
                    'recovery_days_max' => $data['recovery_days_max'],
                    'is_published' => true,
                ],
            );

            $this->seedFishTypes($disease, $data['fish_types']);
            $this->seedTreatments($disease, $data['treatments']);
            $this->seedPreventions($disease, $data['preventions']);
        }

        $this->command?->info(sprintf('%d penyakit tersinkron (FR-10.11).', count(self::DISEASES)));
    }

    /**
     * @param  array<int, string>  $fishTypes
     */
    private function seedFishTypes(Disease $disease, array $fishTypes): void
    {
        $existing = $disease->fishTypes()->pluck('fish_type')->all();

        // Builder::insert() mengabaikan clause where, jadi selisihnya dihitung manual
        // agar kena unique (disease_id, fish_type) saat seeder dijalankan ulang.
        $missing = array_values(array_diff($fishTypes, $existing));

        if ($missing !== []) {
            $disease->fishTypes()->insert(
                array_map(
                    fn (string $fishType) => ['disease_id' => $disease->id, 'fish_type' => $fishType],
                    $missing,
                ),
            );
        }

        // Buang ikan yang tidak lagi ada di daftar (mis. label berubah).
        $disease->fishTypes()->whereNotIn('fish_type', $fishTypes)->delete();
    }

    /**
     * @param  array<int, array{title: string, description: string, medicines?: array|null, duration_days?: int|null}>  $treatments
     */
    private function seedTreatments(Disease $disease, array $treatments): void
    {
        $disease->treatments()->delete();

        foreach ($treatments as $index => $treatment) {
            $disease->treatments()->create([
                'step_order' => $index + 1,
                'title' => $treatment['title'],
                'description' => $treatment['description'],
                'medicines' => $treatment['medicines'] ?? null,
                'duration_days' => $treatment['duration_days'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $preventions
     */
    private function seedPreventions(Disease $disease, array $preventions): void
    {
        $disease->preventions()->delete();

        foreach ($preventions as $index => $prevention) {
            $disease->preventions()->create([
                'step_order' => $index + 1,
                'description' => $prevention,
            ]);
        }
    }
}
