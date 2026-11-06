<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanResult extends Model
{
    protected $fillable = [
        'scan_id', 'disease_id', 'confidence_score', 'top_predictions',
        'low_confidence_warning', 'processing_time_ms', 'model_version',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'float',
            'top_predictions' => 'array',
            'low_confidence_warning' => 'boolean',
            'processing_time_ms' => 'integer',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(FishScan::class, 'scan_id');
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
