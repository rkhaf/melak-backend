<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseasePrevention extends Model
{
    protected $fillable = ['disease_id', 'step_order', 'description'];

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
