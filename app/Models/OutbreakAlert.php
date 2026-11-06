<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutbreakAlert extends Model
{
    protected $fillable = [
        'disease_id', 'province', 'cases_7d', 'threshold', 'message', 'status',
    ];

    protected function casts(): array
    {
        return ['cases_7d' => 'integer', 'threshold' => 'integer'];
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
