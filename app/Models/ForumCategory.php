<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'icon', 'description', 'sort_order', 'post_count',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'post_count' => 'integer'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'category_id');
    }
}
