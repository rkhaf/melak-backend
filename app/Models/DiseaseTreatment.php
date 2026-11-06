<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseTreatment extends Model
{
    protected $fillable = [
        'disease_id', 'step_order', 'title', 'description', 'medicines', 'duration_days',
    ];

    protected function casts(): array
    {
        return ['medicines' => 'array'];
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
