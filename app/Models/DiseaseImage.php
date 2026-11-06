<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseImage extends Model
{
    protected $fillable = ['disease_id', 's3_key', 's3_url', 'caption', 'is_primary', 'order_index'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
