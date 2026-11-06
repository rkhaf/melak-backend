<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Long-Polling Scan (API.md §4.3, §9.1)
    |--------------------------------------------------------------------------
    | GET /api/scans/{id}?wait= ditahan server sampai status keluar dari
    | processing. Plafon maksimum 300 detik (5 menit).
    */

    'scan' => [
        'wait_default' => (int) env('SCAN_WAIT_DEFAULT', 120),
        'wait_max' => (int) env('SCAN_WAIT_MAX', 300),
        'poll_interval_ms' => (int) env('SCAN_POLL_INTERVAL_MS', 1000),
        'low_confidence_threshold' => (float) env('LOW_CONFIDENCE_THRESHOLD', 60),
        'estimated_seconds' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Upload Gambar (FR-02.1.1, API.md §2.7)
    |--------------------------------------------------------------------------
    */

    'upload' => [
        'disk' => env('UPLOAD_DISK', null),
        'max_file_mb' => (int) env('UPLOAD_MAX_FILE_MB', 10),
        'max_images_per_scan' => (int) env('UPLOAD_MAX_IMAGES_PER_SCAN', 5),
        'max_images_per_post' => (int) env('UPLOAD_MAX_IMAGES_PER_POST', 5),
        'max_images_per_comment' => (int) env('UPLOAD_MAX_IMAGES_PER_COMMENT', 3),
        'compress_target_kb' => (int) env('UPLOAD_COMPRESS_TARGET_KB', 500),
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | ML Service (FR-10, API.md §10)
    |--------------------------------------------------------------------------
    | driver = fake  → data uji semu (acak) untuk development & test, tanpa Python
    | driver = http  → panggil FastAPI sungguhan (server-to-server)
    */

    'ml' => [
        'driver' => env('ML_DRIVER', 'fake'),
        // 8009: port ML service; 8000 sudah dipakai backend (run_dev.ps1).
        'base_url' => rtrim(env('ML_BASE_URL', 'http://127.0.0.1:8009'), '/'),
        'timeout' => (int) env('ML_TIMEOUT', 30),
        'connect_timeout' => (int) env('ML_CONNECT_TIMEOUT', 5),
        // FR-10.8 — minta GradCAM saat prediksi; hanya diekspos bila klien
        // menambah ?gradcam=1, jadi biayanya murah.
        'gradcam' => filter_var(env('ML_GRADCAM', true), FILTER_VALIDATE_BOOL),
        'fake_delay_ms' => (int) env('ML_FAKE_DELAY_MS', 0),
        'fake_model_version' => env('ML_FAKE_MODEL_VERSION', 'fake-v1'),
        // Label map di repo monorepo (sebelah folder backend). Sumber urutan
        // indeks model; berisi disease_id yang diisi `php artisan ml:sync-label-map`.
        'label_map' => env('ML_LABEL_MAP', base_path('../ml-service/label_map.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Forum Komunitas (API.md §6, FR-05)
    |--------------------------------------------------------------------------
    */

    'forum' => [
        'blocked_words' => array_values(array_filter(array_map('trim', explode(',', (string) env('FORUM_BLOCKED_WORDS', ''))))),
        'max_tags_per_post' => 5,
        'comment_max_depth' => 2,
        'post_detail_view_increment_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Wabah (API.md §8.7, FR-06.4)
    |--------------------------------------------------------------------------
    | OutbreakAlert dibuat saat ≥ threshold pengguna unik terdeteksi penyakit
    | yang sama pada satu provinsi dalam window hari terakhir.
    */

    'outbreak' => [
        'threshold' => (int) env('OUTBREAK_THRESHOLD', 5),
        'window_days' => (int) env('OUTBREAK_WINDOW_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Prediksi
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'name' => env('NGINGU_QUEUE', 'scans'),
        'tries' => 1,
        'timeout' => 35,
    ],
];
