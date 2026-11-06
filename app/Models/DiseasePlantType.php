<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseasePlantType extends Model
{
    public $timestamps = false;

    protected $fillable = ['disease_id', 'plant_type'];

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
