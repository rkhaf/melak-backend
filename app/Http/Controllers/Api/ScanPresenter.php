<?php

namespace App\Http\Controllers\Api;

use App\Models\PlantScan;
use App\Support\ImageStorage;

/**
 * Ringkasan scan untuk payload list (dashboard & laporan kebun).
 */
trait ScanPresenter
{
    private function scanSummary(PlantScan $scan): array
    {
        $primary = $scan->images->firstWhere('is_primary') ?? $scan->images->first();
        $storage = app(ImageStorage::class);

        return [
            'scan_id' => $scan->id,
            'thumbnail_url' => $primary ? $storage->url($storage->thumbKey($primary->s3_key)) : null,
            'plant_type' => $scan->plant_type,
            'garden' => $scan->garden ? ['id' => $scan->garden->id, 'name' => $scan->garden->name] : null,
            'disease' => $scan->result?->disease ? [
                'id' => $scan->result->disease->id,
                'name' => $scan->result->disease->name,
                'slug' => $scan->result->disease->slug,
                'severity' => $scan->result->disease->severity,
            ] : null,
            'confidence' => $scan->result?->confidence_score,
            'status' => $scan->status,
            'created_at' => $scan->created_at?->toIso8601String(),
        ];
    }
}
