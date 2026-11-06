<?php

namespace Tests\Feature\Admin;

class AdminPanelAccessTest extends AdminTestCase
{
    private array $protectedRoutes = [
        'admin.dashboard',
        'admin.forum.index',
        'admin.reports.index',
        'admin.diseases.index',
        'admin.diseases.create',
        'admin.models.index',
        'admin.tips.index',
        'admin.tips.create',
        'admin.settings.index',
        'admin.users.index',
        'admin.audit-logs.index',
    ];

    public function test_guest_is_redirected_to_login_from_every_protected_page(): void
    {
        foreach ($this->protectedRoutes as $route) {
            $this->get(route($route))
                ->assertRedirect(route('admin.login'));
        }
    }

    public function test_regular_user_is_denied_and_logged_out(): void
    {
        $user = $this->createRegularUser();
        $this->actingAs($user);

        foreach (['admin.dashboard', 'admin.forum.index', 'admin.diseases.index', 'admin.models.index'] as $route) {
            $this->get(route($route))
                ->assertRedirect(route('admin.login'));
        }

        $this->assertGuest();
    }

    public function test_admin_dashboard_renders_with_platform_stats(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Moderasi')
            ->assertSee('Total Pengguna')
            ->assertSee('Export CSV');
    }

    public function test_admin_can_export_dashboard_stats_as_csv(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.dashboard.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Statistik Platform Melak', (string) $response->streamedContent());
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.forum.index'))->assertOk()->assertSee('Antrean Moderasi');
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Laporan Konten Forum');
        $this->get(route('admin.diseases.index'))->assertOk()->assertSee('Ensiklopedia Penyakit');
        $this->get(route('admin.diseases.create'))->assertOk()->assertSee('Tambah Penyakit');
        $this->get(route('admin.models.index'))->assertOk()->assertSee('Manajemen Model ML');
        $this->get(route('admin.tips.index'))->assertOk()->assertSee('Tips Budidaya Ikan');
        $this->get(route('admin.tips.create'))->assertOk()->assertSee('Tambah Tips');
        $this->get(route('admin.settings.index'))->assertOk()->assertSee('Pengaturan Aplikasi');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Daftar Pengguna');
    }
}
