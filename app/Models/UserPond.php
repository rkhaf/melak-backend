<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPond extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'location_lat',
        'location_lng',
        'location_label',
        'area_m2',
        'capacity',
        'fish_types',
        'health_score',
    ];

    protected function casts(): array
    {
        return [
            'fish_types' => 'array',
            'health_score' => 'integer',
            'area_m2' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(FishScan::class, 'pond_id');
    }
}
