<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlModel extends Model
{
    protected $fillable = [
        'version', 'filename', 's3_path', 'disease_labels', 'accuracy_pct',
        'is_active', 'uploaded_by', 'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'disease_labels' => 'array',
            'is_active' => 'boolean',
            'accuracy_pct' => 'float',
            'activated_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
