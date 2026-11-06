<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseFishType extends Model
{
    public $timestamps = false;

    protected $fillable = ['disease_id', 'fish_type'];

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
