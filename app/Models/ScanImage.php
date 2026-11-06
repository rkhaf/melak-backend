<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanImage extends Model
{
    protected $fillable = [
        'scan_id', 's3_key', 's3_url', 'is_primary', 'gradcam_url', 'order_index',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(FishScan::class, 'scan_id');
    }
}
