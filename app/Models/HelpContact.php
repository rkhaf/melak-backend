<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpContact extends Model
{
    protected $fillable = [
        'type',
        'title',
        'value',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
