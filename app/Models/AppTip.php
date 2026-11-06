<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppTip extends Model
{
    protected $fillable = ['content', 'category', 'is_active', 'published_on'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_on' => 'date',
        ];
    }
}
