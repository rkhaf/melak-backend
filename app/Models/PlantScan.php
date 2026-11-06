<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlantScan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'garden_id',
        'plant_type',
        'symptoms_desc',
        'location_lat',
        'location_lng',
        'kecamatan',
        'status',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'location_lat' => 'float',
            'location_lng' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function garden(): BelongsTo
    {
        return $this->belongsTo(UserGarden::class, 'garden_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ScanImage::class, 'scan_id')->orderBy('order_index');
    }

    public function result(): HasOne
    {
        return $this->hasOne(ScanResult::class, 'scan_id');
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['done', 'failed']);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['done', 'failed'], true);
    }
}
