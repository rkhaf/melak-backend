<?php

namespace App\Jobs;

use App\Models\Disease;
use App\Models\FishScan;
use App\Models\ScanResult;
use App\Notifications\ScanResult as ScanResultNotification;
use App\Services\MlClient;
use App\Services\MlException;
use App\Support\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Proses prediksi satu scan (SRS §4.3): gambar → ML → simpan hasil → status done/failed.
 */
class ProcessScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 35;

    public function __construct(public string $scanId)
    {
        $this->onQueue((string) config('ngingu.queue.name', 'scans'));
    }

    public function handle(MlClient $ml): void
    {
        $scan = FishScan::find($this->scanId);

        if (! $scan || $scan->isFinished()) {
            return;
        }

        $scan->update(['status' => 'processing']);

        try {
            $prediction = $ml->predict(
                $scan->id,
                $scan->images()->pluck('s3_key')->all(),
                $scan->fish_type,
            );
        } catch (MlException $e) {
            $scan->update([
                'status' => 'failed',
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
            ]);

            return;
        } catch (\Throwable $e) {
            Log::error('Scan ML gagal', ['scan_id' => $scan->id, 'error' => $e->getMessage()]);

            $scan->update([
                'status' => 'failed',
                'error_code' => 'ML_ERROR',
                'error_message' => 'Prediksi gagal diproses. Coba lagi.',
            ]);

            return;
        }

        $disease = $this->resolveDisease($prediction);
        $confidence = (float) $prediction['confidence'];

        ScanResult::updateOrCreate(
            ['scan_id' => $scan->id],
            [
                'disease_id' => $disease?->id,
                'confidence_score' => $confidence,
                'top_predictions' => $prediction['top_predictions'],
                // Nilai sudah distandarkan adapter; fallback ke threshold lokal
                // bila payload lama tidak membawanya.
                'low_confidence_warning' => (bool) ($prediction['low_confidence_warning']
                    ?? $confidence < (float) config('ngingu.scan.low_confidence_threshold', 60)),
                'processing_time_ms' => $prediction['processing_time_ms'],
                'model_version' => $prediction['model_version'],
            ]
        );

        $scan->update(['status' => 'done']);

        $this->storeGradcam($scan, $prediction['gradcam_url'] ?? null);

        try {
            $this->notify($scan, $disease, $confidence, $prediction['gradcam_url']);
        } catch (\Throwable $e) {
            // Notifikasi bersifat best-effort: hasil scan sudah `done`, kegagalan
            // email/in-app tidak boleh menggagalkan job (queue sync = response 500).
            Log::error('Gagal mengirim notifikasi hasil scan', [
                'scan_id' => $scan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * GradCAM disimpan di foto utama — dipakai ScanController saat ?gradcam=1 (FR-10.8).
     */
    private function storeGradcam(FishScan $scan, ?string $gradcamUrl): void
    {
        if ($gradcamUrl === null) {
            return;
        }

        $image = $scan->images()
            ->orderByDesc('is_primary')
            ->orderBy('order_index')
            ->first();

        $image?->update(['gradcam_url' => $gradcamUrl]);
    }

    private function resolveDisease(array $prediction): ?Disease
    {
        $slug = $prediction['disease_slug'];

        return Disease::query()
            ->published()
            ->where('slug', $slug)
            ->orWhere('name', $prediction['disease_name'])
            ->first();
    }

    private function notify(FishScan $scan, ?Disease $disease, float $confidence, ?string $gradcamUrl): void
    {
        $name = $disease?->name ?? 'Tidak diketahui';

        Notifier::send(
            $scan->user,
            'scan_result',
            'Hasil scan siap',
            "Prediksi: {$name} (confidence {$confidence}%).",
            [
                'scan_id' => $scan->id,
                'disease_slug' => $disease?->slug,
                'confidence' => $confidence,
                'gradcam_url' => $gradcamUrl,
            ],
            fn () => $scan->user->notify(new ScanResultNotification($scan, $disease, $confidence)),
        );
    }
}
