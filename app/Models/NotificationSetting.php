<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id', 'type', 'via_push', 'via_email', 'via_inapp',
    ];

    protected function casts(): array
    {
        return [
            'via_push' => 'boolean',
            'via_email' => 'boolean',
            'via_inapp' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
