<?php

/**
 * E2E: MlClient (driver http) -> ML service sungguhan -> MlPredictionNormalizer.
 *
 * Jalankan: php scripts/ml_e2e.php
 * Syarat: ML service jalan di 127.0.0.1:8009 (python serve.py) + model sudah siap.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MlClient;
use App\Services\MlException;
use App\Services\MlProbe;
use App\Support\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

config([
    'ngingu.ml.driver' => 'http',
    'ngingu.ml.base_url' => getenv('ML_BASE_URL') ?: 'http://127.0.0.1:8009',
    'ngingu.ml.gradcam' => true,
]);

$storage = app(ImageStorage::class);
$disk = $storage->disk();

echo 'driver   : '.config('ngingu.ml.driver')."\n";
echo 'base_url : '.config('ngingu.ml.base_url')."\n";
echo "disk     : {$disk}\n\n";

// ── kasus 1: satu foto ──────────────────────────────────────────────
$source = $argv[1] ?? 'C:\Users\REDMI\AppData\Local\Temp\opencode\ikan_test.jpg';
$key = 'e2e/single-'.uniqid().'.jpg';
Storage::disk($disk)->put($key, file_get_contents($source));

echo "== 1 foto ({$key}) ==\n";

$start = microtime(true);

try {
    $result = app(MlClient::class)->predict('e2e-scan', [$key], 'Lele');
} catch (MlException $e) {
    echo "GAGAL: [{$e->errorCode}] {$e->getMessage()} (HTTP {$e->getCode()})\n";
    exit(1);
}

printf("waktu    : %.1f detik\n\n", microtime(true) - $start);

foreach ($result as $k => $v) {
    $shown = is_string($v) && strlen($v) > 90 ? substr($v, 0, 90).'… ('.strlen($v).' char)' : json_encode($v, JSON_UNESCAPED_UNICODE);
    printf("  %-24s %s\n", $k, $shown);
}

// Pastikan GradCAM benar-benar tersimpan sebagai file, bukan base64 di DB.
$gradcam = $result['gradcam_url'];
echo "\ngradcam tersimpan: ";
if ($gradcam === null) {
    echo "TIDAK (null)\n";
} else {
    $path = parse_url($gradcam, PHP_URL_PATH) ?: '';
    $relative = str_replace('/storage/', '', $path);
    echo (Storage::disk($disk)->exists($relative) ? 'YA' : 'TIDAK ADA')."\n";
    echo "  url      : {$gradcam}\n";
    echo "  key      : {$relative}\n";
    echo "  panjang  : ".Storage::disk($disk)->size($relative)." byte (base64 asli: ".strlen($result['gradcam_url'] ?? '').")\n";
}

// ── kasus 2: banyak foto -> /predict/batch ──────────────────────────
echo "\n== 2 foto (batch) ==\n";

$key2 = 'e2e/batch-a-'.uniqid().'.jpg';
$key3 = 'e2e/batch-b-'.uniqid().'.jpg';
Storage::disk($disk)->put($key2, file_get_contents($source));
Storage::disk($disk)->put($key3, file_get_contents($source));

$start = microtime(true);

try {
    $batch = app(MlClient::class)->predict('e2e-scan-batch', [$key2, $key3], null);
} catch (MlException $e) {
    echo "GAGAL: [{$e->errorCode}] {$e->getMessage()} (HTTP {$e->getCode()})\n";
    exit(1);
}

printf("waktu    : %.1f detik\n", microtime(true) - $start);
echo "disease  : {$batch['disease_name']} ({$batch['disease_slug']})\n";
printf("conf     : %.1f%%\n", $batch['confidence']);
echo "warnings : ".var_export($batch['low_confidence_warning'], true)."\n";
echo "model    : {$batch['model_version']}\n";

// ── kasus 3: MlProbe — test request dari panel admin (API.md §10) ──────
echo "\n== 3. MlProbe (/health + test request admin) ==\n";

$probe = app(MlProbe::class);

$health = $probe->health();
echo 'health   : '.($health['ok'] ? 'OK' : 'GAGAL')
    .' · HTTP '.($health['http_status'] ?? '-')
    .' · '.$health['elapsed_ms'].' ms';

if (is_array($health['body'])) {
    echo ' · status='.$health['body']['status'].' model='.$health['body']['model_version'];
}
echo "\n";

$uploaded = new UploadedFile($source, basename($source), 'image/jpeg', null, true);

$probeStart = microtime(true);
$probeResult = $probe->predict($uploaded, 'Lele', false);

printf("waktu    : %.1f detik\n", microtime(true) - $probeStart);
echo 'endpoint : '.$probeResult['endpoint']." (driver {$probeResult['driver']})\n";
echo 'http     : '.($probeResult['http_status'] ?? '-')."\n";

if ($probeResult['error'] !== null) {
    echo "GAGAL: [{$probeResult['error']['code']}] {$probeResult['error']['message']}"
        .' (HTTP '.$probeResult['error']['status'].")\n";
    exit(1);
}

$normalized = $probeResult['normalized'];
printf("raw      : %d byte JSON\n", strlen((string) json_encode($probeResult['raw'])));
echo 'disease  : '.$normalized['disease_name']." ({$normalized['disease_slug']})\n";
printf("conf     : %.1f%%\n", $normalized['confidence']);
echo 'resolusi : '.($probeResult['disease']['found']
    ? "DITEMUKAN — id {$probeResult['disease']['disease_id']}, severity {$probeResult['disease']['severity']}"
    : 'TIDAK DITEMUKAN — '.$probeResult['disease']['hint'])."\n";

// ── bersih-bersih ───────────────────────────────────────────────────
foreach ([$key, $key2, $key3] as $cleanup) {
    Storage::disk($disk)->delete($cleanup);
}

if ($gradcam !== null) {
    $path = parse_url($gradcam, PHP_URL_PATH) ?: '';
    Storage::disk($disk)->delete(str_replace('/storage/', '', $path));
}

echo "\n[OK] selesai.\n";
