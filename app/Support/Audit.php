<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Audit
{
    /**
     * Record an admin activity.
     */
    public static function log(string $action, string $description = '', $subject = null): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => is_object($subject) ? get_class($subject) : null,
                'model_id' => is_object($subject) ? $subject->getKey() : null,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai proses utama gagal karena logging.
            report($e);
        }
    }
}
