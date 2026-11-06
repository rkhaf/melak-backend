<?php

namespace Database\Seeders;

use App\Models\Disease;
use Illuminate\Database\Seeder;

class PlantDiseaseSeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private const DISEASES = [
        [
            'slug' => 'tomato-bacterial-spot',
            'name' => 'Bercak Bakteri pada Tomat',
            'name_en' => 'Tomato Bacterial Spot',
            'model_label' => 'Tomato___Bacterial_spot',
            'category' => 'bacteria',
            'severity' => 'sedang',
            'description' => 'Penyakit bakteri pada tomat yang menimbulkan bercak kecil gelap pada daun dan buah. Serangan berat dapat menyebabkan daun menguning dan gugur.',
            'causes' => 'Disebabkan bakteri Xanthomonas yang menyebar melalui benih atau bibit terinfeksi, percikan air, alat kebun, dan sisa tanaman sakit.',
            'symptoms' => "Bercak kecil gelap dengan tepi kekuningan pada daun\nBercak kasar atau seperti keropeng pada buah\nDaun menguning dan gugur saat serangan berat",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Tomat'],
            'treatments' => [
                ['title' => 'Buang bagian yang terinfeksi', 'description' => 'Pangkas daun dan buah yang menunjukkan gejala, lalu buang di luar area tanam. Bersihkan alat pangkas setelah digunakan.', 'duration_days' => 2],
                ['title' => 'Kurangi kelembapan daun', 'description' => 'Hindari penyiraman dari atas dan beri jarak antartanaman agar daun cepat kering.', 'duration_days' => 7],
            ],
            'preventions' => [
                'Gunakan benih sehat dan bibit yang bebas gejala.',
                'Bersihkan alat kebun dan hindari bekerja pada tanaman saat basah.',
            ],
        ],
        [
            'slug' => 'tomato-early-blight',
            'name' => 'Bercak Alternaria pada Tomat',
            'name_en' => 'Tomato Early Blight',
            'model_label' => 'Tomato___Early_blight',
            'category' => 'fungi',
            'severity' => 'sedang',
            'description' => 'Penyakit jamur yang biasanya muncul lebih dahulu pada daun tua tomat dan dapat merambat ke batang serta buah.',
            'causes' => 'Jamur Alternaria solani bertahan pada sisa tanaman dan tanah, lalu menyebar lewat percikan air. Kelembapan tinggi dan tanaman yang lemah meningkatkan risiko.',
            'symptoms' => "Bercak cokelat dengan lingkaran konsentris seperti sasaran\nDaun bawah menguning lalu mengering\nBercak gelap dapat muncul pada batang dan pangkal buah",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Tomat'],
            'treatments' => [
                ['title' => 'Sanitasi tanaman', 'description' => 'Buang daun dan sisa tanaman bergejala. Jangan memasukkan bahan terinfeksi ke kompos yang tidak mencapai suhu sanitasi.', 'duration_days' => 2],
                ['title' => 'Perbaiki sirkulasi udara', 'description' => 'Pangkas daun bawah seperlunya dan hindari membasahi tajuk saat menyiram.', 'duration_days' => 7],
                ['title' => 'Evaluasi pengendalian penyakit', 'description' => 'Jika gejala terus menyebar, minta saran penyuluh setempat mengenai fungisida yang terdaftar untuk tanaman dan penyakit tersebut; ikuti label produk.', 'duration_days' => 14],
            ],
            'preventions' => [
                'Rotasikan tomat dengan tanaman bukan famili terung-terungan.',
                'Bersihkan sisa tanaman setelah panen dan gunakan mulsa untuk mengurangi percikan tanah.',
            ],
        ],
        [
            'slug' => 'tomato-late-blight',
            'name' => 'Busuk Daun pada Tomat',
            'name_en' => 'Tomato Late Blight',
            'model_label' => 'Tomato___Late_blight',
            'category' => 'fungi',
            'severity' => 'berat',
            'description' => 'Penyakit busuk daun yang dapat berkembang cepat pada tomat saat cuaca sejuk dan lembap, serta merusak daun, batang, dan buah.',
            'causes' => 'Disebabkan Phytophthora infestans, organisme mirip jamur yang menyebar melalui spora pada kondisi lembap dan dapat terbawa bibit atau umbi inang terinfeksi.',
            'symptoms' => "Bercak basah tidak beraturan yang cepat membesar\nLapisan putih halus dapat terlihat di sisi bawah daun saat lembap\nBercak cokelat gelap pada batang dan buah",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Tomat'],
            'treatments' => [
                ['title' => 'Isolasi dan buang tanaman parah', 'description' => 'Singkirkan bagian atau tanaman yang terinfeksi berat dengan hati-hati untuk mengurangi sumber spora.', 'duration_days' => 1],
                ['title' => 'Jaga daun tetap kering', 'description' => 'Siram di pangkal tanaman, tingkatkan jarak tanam bila memungkinkan, dan hindari bekerja saat tajuk basah.', 'duration_days' => 7],
                ['title' => 'Minta arahan pengendalian lokal', 'description' => 'Penyakit ini dapat menyebar cepat. Konsultasikan pengendalian yang terdaftar dan sesuai kondisi setempat kepada penyuluh pertanian.', 'duration_days' => 7],
            ],
            'preventions' => [
                'Periksa tanaman secara rutin, terutama setelah periode hujan atau embun panjang.',
                'Jangan menanam bibit tomat atau kentang yang menunjukkan gejala.',
            ],
        ],
        [
            'slug' => 'tomato-leaf-mold',
            'name' => 'Kapang Daun Tomat',
            'name_en' => 'Tomato Leaf Mold',
            'model_label' => 'Tomato___Leaf_Mold',
            'category' => 'fungi',
            'severity' => 'sedang',
            'description' => 'Penyakit jamur yang terutama menyerang daun tomat pada lingkungan dengan kelembapan tinggi, khususnya pada budidaya terlindung.',
            'causes' => 'Jamur Passalora fulva berkembang pada kelembapan tinggi dan sirkulasi udara buruk; spora menyebar melalui udara, air, dan alat.',
            'symptoms' => "Bercak hijau pucat atau kekuningan di permukaan atas daun\nLapisan beludru hijau zaitun hingga keabu-abuan di bawah daun\nDaun mengering dan menggulung saat serangan berat",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Tomat'],
            'treatments' => [
                ['title' => 'Buang daun bergejala', 'description' => 'Pangkas daun yang terinfeksi dan keluarkan dari area tanam. Hindari memangkas terlalu banyak daun sekaligus.', 'duration_days' => 2],
                ['title' => 'Turunkan kelembapan', 'description' => 'Tingkatkan ventilasi, atur jarak tanam, dan siram pada pagi hari di pangkal tanaman.', 'duration_days' => 7],
            ],
            'preventions' => [
                'Jaga sirkulasi udara pada rumah tanam dan hindari tajuk terlalu rimbun.',
                'Bersihkan sisa tanaman dan desinfeksi alat setelah pemangkasan.',
            ],
        ],
        [
            'slug' => 'tomato-healthy',
            'name' => 'Tomat Sehat',
            'name_en' => 'Tomato Healthy',
            'model_label' => 'Tomato___healthy',
            'category' => 'other',
            'severity' => 'ringan',
            'description' => 'Tidak tampak gejala penyakit umum pada bagian tanaman tomat yang terlihat di foto. Hasil ini bukan jaminan tanaman bebas dari semua gangguan.',
            'causes' => 'Prediksi sehat menunjukkan tidak ditemukan pola gejala yang dikenali model; foto yang kurang jelas atau gejala dini dapat memengaruhi hasil.',
            'symptoms' => "Daun tampak hijau tanpa bercak mencolok\nBatang dan buah yang terlihat tidak menunjukkan kerusakan nyata",
            'recovery_days_min' => null,
            'recovery_days_max' => null,
            'plant_types' => ['Tomat'],
            'treatments' => [],
            'preventions' => [
                'Pantau daun, batang, dan buah secara rutin.',
                'Jaga penyiraman, pemupukan, dan sanitasi kebun secara seimbang.',
            ],
        ],
        [
            'slug' => 'maize-gray-leaf-spot',
            'name' => 'Bercak Daun Kelabu pada Jagung',
            'name_en' => 'Corn Cercospora Leaf Spot',
            'model_label' => 'Corn_(maize)___Cercospora_leaf_spot Gray_leaf_spot',
            'category' => 'fungi',
            'severity' => 'sedang',
            'description' => 'Penyakit jamur pada jagung yang membentuk lesi memanjang dan dapat mengurangi luas daun produktif.',
            'causes' => 'Cercospora zeae-maydis atau spesies terkait bertahan pada residu tanaman jagung dan berkembang saat kelembapan tinggi serta tajuk rapat.',
            'symptoms' => "Lesi persegi panjang berwarna cokelat hingga kelabu mengikuti tulang daun\nBercak sering muncul lebih dahulu pada daun bawah\nLesi dapat menyatu dan menyebabkan daun mengering",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Jagung'],
            'treatments' => [
                ['title' => 'Pantau penyebaran bercak', 'description' => 'Periksa daun bawah dan tengah secara berkala untuk menilai perkembangan gejala pada petak.', 'duration_days' => 7],
                ['title' => 'Kelola residu dan kelembapan', 'description' => 'Atur jarak tanam dan pengelolaan sisa tanaman sesuai praktik setempat untuk mengurangi sumber inokulum.', 'duration_days' => 14],
            ],
            'preventions' => [
                'Gunakan varietas jagung yang sesuai dan toleran bila tersedia.',
                'Lakukan rotasi tanaman dan kelola residu tanaman setelah panen.',
            ],
        ],
        [
            'slug' => 'maize-common-rust',
            'name' => 'Karat Daun Jagung',
            'name_en' => 'Corn Common Rust',
            'model_label' => 'Corn_(maize)___Common_rust_',
            'category' => 'fungi',
            'severity' => 'sedang',
            'description' => 'Penyakit karat yang menghasilkan pustula pada daun jagung. Dampak meningkat bila tanaman muda terinfeksi berat dan cuaca mendukung perkembangan penyakit.',
            'causes' => 'Jamur Puccinia sorghi menyebar melalui spora yang terbawa angin; kelembapan tinggi mendukung infeksi.',
            'symptoms' => "Pustula kecil berwarna cokelat kemerahan pada permukaan daun\nPustula dapat muncul di kedua sisi daun dan meninggalkan serbuk saat tersentuh\nDaun menguning saat serangan berat",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Jagung'],
            'treatments' => [
                ['title' => 'Identifikasi dan pantau gejala', 'description' => 'Bandingkan pustula pada beberapa tanaman dan catat penyebarannya; konsultasikan diagnosis jika gejala tidak khas.', 'duration_days' => 7],
                ['title' => 'Pertimbangkan pengendalian sesuai kebutuhan', 'description' => 'Bila serangan mengancam hasil, konsultasikan pilihan fungisida terdaftar kepada penyuluh dan ikuti label produk.', 'duration_days' => 7],
            ],
            'preventions' => [
                'Pilih varietas yang memiliki ketahanan terhadap karat bila tersedia.',
                'Lakukan pemantauan sejak fase vegetatif dan hindari penanaman varietas rentan secara berulang.',
            ],
        ],
        [
            'slug' => 'maize-northern-leaf-blight',
            'name' => 'Hawar Daun Jagung',
            'name_en' => 'Corn Northern Leaf Blight',
            'model_label' => 'Corn_(maize)___Northern_Leaf_Blight',
            'category' => 'fungi',
            'severity' => 'berat',
            'description' => 'Penyakit hawar daun yang dapat menurunkan fotosintesis jagung, terutama jika infeksi terjadi sebelum pembentukan biji.',
            'causes' => 'Jamur Exserohilum turcicum bertahan pada residu jagung dan menyebar melalui spora pada cuaca lembap.',
            'symptoms' => "Lesi panjang berbentuk cerutu berwarna kelabu kehijauan\nLesi membesar mengikuti arah tulang daun\nDaun dapat mengering ketika banyak lesi menyatu",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Jagung'],
            'treatments' => [
                ['title' => 'Pantau tingkat serangan', 'description' => 'Periksa beberapa titik dalam petak dan catat daun yang bergejala serta fase pertumbuhan tanaman.', 'duration_days' => 7],
                ['title' => 'Kelola sisa tanaman', 'description' => 'Kurangi sumber penyakit dari residu jagung sesuai sistem olah tanah dan rekomendasi penyuluh setempat.', 'duration_days' => 14],
            ],
            'preventions' => [
                'Gunakan varietas tahan atau toleran yang sesuai wilayah.',
                'Rotasikan jagung dengan tanaman bukan inang dan hindari residu terinfeksi menumpuk.',
            ],
        ],
        [
            'slug' => 'maize-healthy',
            'name' => 'Jagung Sehat',
            'name_en' => 'Corn Healthy',
            'model_label' => 'Corn_(maize)___healthy',
            'category' => 'other',
            'severity' => 'ringan',
            'description' => 'Tidak tampak gejala penyakit umum pada bagian tanaman jagung yang terlihat di foto. Tetap lakukan pengamatan berkala di seluruh petak.',
            'causes' => 'Prediksi sehat menunjukkan tidak ditemukan pola gejala yang dikenali model; kualitas foto dan tahap awal penyakit dapat memengaruhi hasil.',
            'symptoms' => "Daun yang terlihat tidak menunjukkan bercak atau lesi khas\nWarna dan bentuk bagian tanaman yang terlihat tampak normal",
            'recovery_days_min' => null,
            'recovery_days_max' => null,
            'plant_types' => ['Jagung'],
            'treatments' => [],
            'preventions' => [
                'Pantau tanaman di beberapa bagian petak, bukan hanya satu titik.',
                'Gunakan benih sehat dan praktik budidaya sesuai kondisi lahan.',
            ],
        ],
        [
            'slug' => 'bell-pepper-bacterial-spot',
            'name' => 'Bercak Bakteri pada Paprika',
            'name_en' => 'Bell Pepper Bacterial Spot',
            'model_label' => 'Pepper,_bell___Bacterial_spot',
            'category' => 'bacteria',
            'severity' => 'sedang',
            'description' => 'Penyakit bakteri pada paprika yang dapat menimbulkan bercak pada daun dan buah serta menyebabkan gugurnya daun.',
            'causes' => 'Bakteri Xanthomonas menyebar melalui benih atau bibit terinfeksi, percikan air, dan alat kebun yang tidak dibersihkan.',
            'symptoms' => "Bercak kecil gelap dengan tepi kekuningan pada daun\nBercak menonjol atau kasar pada buah\nDaun menguning dan gugur pada serangan berat",
            'recovery_days_min' => 7,
            'recovery_days_max' => 21,
            'plant_types' => ['Paprika', 'Cabai'],
            'treatments' => [
                ['title' => 'Singkirkan bagian terinfeksi', 'description' => 'Buang daun atau buah bergejala dan bersihkan alat pemangkasan sebelum digunakan kembali.', 'duration_days' => 2],
                ['title' => 'Hindari penyebaran melalui air', 'description' => 'Siram di pangkal tanaman dan kurangi pekerjaan pada tanaman saat daun basah.', 'duration_days' => 7],
            ],
            'preventions' => [
                'Pilih benih dan bibit sehat dari sumber tepercaya.',
                'Bersihkan alat dan lakukan rotasi dengan tanaman bukan famili terung-terungan.',
            ],
        ],
        [
            'slug' => 'bell-pepper-healthy',
            'name' => 'Paprika Sehat',
            'name_en' => 'Bell Pepper Healthy',
            'model_label' => 'Pepper,_bell___healthy',
            'category' => 'other',
            'severity' => 'ringan',
            'description' => 'Tidak terlihat gejala penyakit umum pada bagian tanaman paprika yang masuk dalam foto. Pemeriksaan seluruh tanaman tetap disarankan.',
            'causes' => 'Prediksi sehat berarti model tidak mengenali pola penyakit pada foto; gejala yang tersembunyi atau foto buram dapat menghasilkan prediksi berbeda.',
            'symptoms' => "Daun yang terlihat tidak menunjukkan bercak mencolok\nBatang dan buah yang terlihat tampak normal",
            'recovery_days_min' => null,
            'recovery_days_max' => null,
            'plant_types' => ['Paprika', 'Cabai'],
            'treatments' => [],
            'preventions' => [
                'Periksa sisi atas dan bawah daun secara berkala.',
                'Jaga kebersihan kebun dan hindari stres air yang berlebihan.',
            ],
        ],
    ];

    private const LEGACY_DISEASE_SLUGS = [
        'bacterial-red-disease',
        'aeromoniasis',
        'bacterial-gill-disease',
        'saprolegniasis',
        'ikan-sehat',
        'penyakit-parasit',
        'white-tail-disease',
        'shrimp-black-gill',
        'udang-sehat',
        'shrimp-white-spot-wssv',
        'shrimp-wssv-black-gill',
    ];

    public function run(): void
    {
        foreach (self::DISEASES as $data) {
            $disease = Disease::query()->firstOrNew(['slug' => $data['slug']]);
            $disease->fill(collect($data)->except(['model_label', 'plant_types', 'treatments', 'preventions'])->all());
            $disease->is_published = true;
            $disease->save();

            $this->syncPlantTypes($disease, $data['plant_types']);
            $this->syncTreatments($disease, $data['treatments']);
            $this->syncPreventions($disease, $data['preventions']);
        }

        Disease::query()->whereIn('slug', self::LEGACY_DISEASE_SLUGS)->update(['is_published' => false]);
    }

    /**
     * @param  array<int, string>  $plantTypes
     */
    private function syncPlantTypes(Disease $disease, array $plantTypes): void
    {
        $disease->plantTypes()->delete();

        foreach ($plantTypes as $plantType) {
            $disease->plantTypes()->create(['plant_type' => $plantType]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $treatments
     */
    private function syncTreatments(Disease $disease, array $treatments): void
    {
        $disease->treatments()->delete();

        foreach ($treatments as $index => $treatment) {
            $disease->treatments()->create([
                'step_order' => $index + 1,
                ...$treatment,
                'medicines' => $treatment['medicines'] ?? [],
            ]);
        }
    }

    /**
     * @param  array<int, string>  $preventions
     */
    private function syncPreventions(Disease $disease, array $preventions): void
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
