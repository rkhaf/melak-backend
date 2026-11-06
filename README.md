# melak-backend

Backend Laravel untuk klasifikasi penyakit tanaman domestik, panduan perawatan,
riwayat scan, kebun, dan komunitas Melak.

Jenis tanaman contoh yang tersedia di data awal: tomat, jagung, cabai, dan paprika.

## Database dan seeder

Migrasi baru mempertahankan riwayat nama migrasi lama agar database yang sudah
pernah menjalankan migrasi tetap dapat diperbarui dengan `php artisan migrate`.
Tabel domain aktif menggunakan `plant_scans`, `user_gardens`,
`disease_plant_types`, dan kolom `plant_type` / `plant_types`.

Jalankan seeder dengan:

```bash
php artisan db:seed
```

Seeder utama `PlantDiseaseSeeder` mengisi 11 penyakit tanaman beserta jenis
tanaman, panduan penanganan, dan pencegahannya. Seeder mengarsipkan data
penyakit ikan bawaan sebelumnya dengan menandainya tidak dipublikasikan.

## Kontrak API

Scan menggunakan `plant_type` dan `garden_id`; profil serta panduan penyakit
menggunakan `plant_types`. Endpoint manajemen kolam menjadi `/api/gardens`.
Klien yang memakai nama field atau endpoint lama perlu diperbarui.

Backend mengirim field `plant_type` ke layanan ML. Pastikan layanan ML dan
label-map yang digunakan deployment juga memakai label klasifikasi tanaman.
