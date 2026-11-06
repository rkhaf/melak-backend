<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'avatar_url', 'username', 'email', 'password', 'phone', 'role', 'is_active', 'fcm_token', 'bio', 'province', 'plant_types', 'reputation_points', 'reputation_level', 'banned_at', 'ban_reason', 'ban_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plant_types' => 'array',
            'banned_at' => 'datetime',
            'ban_expires_at' => 'datetime',
        ];
    }

    /**
     * Whether the account is currently suspended/banned (FR-09.3.2).
     */
    public function isBanned(): bool
    {
        if ($this->banned_at === null) {
            return false;
        }

        return $this->ban_expires_at === null || $this->ban_expires_at->isFuture();
    }

    /**
     * Get the notifications received by the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the notification settings (one row per notification type) for the user.
     */
    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    /**
     * Get the linked social accounts (Google/Apple) of the user.
     */
    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OauthAccount::class);
    }

    /**
     * Get the plant gardens owned by the user.
     */
    public function gardens(): HasMany
    {
        return $this->hasMany(UserGarden::class);
    }

    /**
     * Get the plant disease scans created by the user.
     */
    public function plantScans(): HasMany
    {
        return $this->hasMany(PlantScan::class);
    }

    /**
     * Get the forum posts authored by the user.
     */
    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    /**
     * Get the forum comments authored by the user.
     */
    public function forumComments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }

    /**
     * Get the reputation log entries of the user.
     */
    public function reputationLogs(): HasMany
    {
        return $this->hasMany(ReputationLog::class);
    }

    /**
     * Get the unread notification count for the user.
     */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->unread()->count();
    }
}
