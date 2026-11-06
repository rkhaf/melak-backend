<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disease extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'category',
        'severity',
        'description',
        'causes',
        'symptoms',
        'recovery_days_min',
        'recovery_days_max',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'recovery_days_min' => 'integer',
            'recovery_days_max' => 'integer',
        ];
    }

    public function fishTypes(): HasMany
    {
        return $this->hasMany(DiseaseFishType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DiseaseImage::class)->orderByDesc('is_primary')->orderBy('order_index');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(DiseaseTreatment::class)->orderBy('step_order');
    }

    public function preventions(): HasMany
    {
        return $this->hasMany(DiseasePrevention::class)->orderBy('step_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scanResults(): HasMany
    {
        return $this->hasMany(ScanResult::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
