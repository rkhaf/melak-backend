<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;

class AuditLogTest extends AdminTestCase
{
    public function test_admin_can_view_audit_logs(): void
    {
        $admin = $this->actingAsAdmin();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'auth.login',
            'description' => 'Administrator masuk.',
        ]);

        $this->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('auth.login');
    }

    public function test_audit_logs_can_be_filtered_by_action(): void
    {
        $admin = $this->actingAsAdmin();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'auth.login',
            'description' => 'Administrator masuk ke sistem.',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user.created',
            'description' => 'Akun baru dibuat oleh admin.',
        ]);

        $this->get(route('admin.audit-logs.index', ['action' => 'user.created']))
            ->assertOk()
            ->assertSee('Akun baru dibuat oleh admin.')
            ->assertDontSee('Administrator masuk ke sistem.');
    }
}
