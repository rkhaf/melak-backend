<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumTag extends Model
{
    protected $fillable = ['name', 'slug', 'usage_count'];

    protected function casts(): array
    {
        return ['usage_count' => 'integer'];
    }
}
