<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'app_name',
    'description',
    'developer',
    'website',
    'support_email',
    'privacy_policy_url',
    'terms_url',
    'privacy_policy_content',
    'terms_content',
    'apk_path',
    'apk_filename',
    'apk_version',
    'apk_uploaded_at',
])]
class AppInfo extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'app_info';

    protected function casts(): array
    {
        return [
            'apk_uploaded_at' => 'datetime',
        ];
    }
}
