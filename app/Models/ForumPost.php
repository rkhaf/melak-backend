<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'scan_id', 'title', 'content_html', 'image_urls', 'type',
        'status', 'is_pinned', 'views_count', 'votes_count', 'comments_count', 'has_best_answer',
    ];

    protected function casts(): array
    {
        return [
            'image_urls' => 'array',
            'is_pinned' => 'boolean',
            'has_best_answer' => 'boolean',
            'views_count' => 'integer',
            'votes_count' => 'integer',
            'comments_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(FishScan::class, 'scan_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'post_id');
    }

    public function follows(): HasMany
    {
        return $this->hasMany(ForumFollow::class, 'post_id');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(ForumBookmark::class, 'post_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ForumReport::class, 'reportable_id')
            ->where('forum_reports.reportable_type', 'post');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ForumTag::class, 'forum_post_tags', 'post_id', 'tag_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
