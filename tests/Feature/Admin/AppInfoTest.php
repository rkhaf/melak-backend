<?php

namespace Tests\Feature\Admin;

use App\Models\AppInfo;

class AppInfoTest extends AdminTestCase
{
    public function test_admin_can_view_app_info_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.app-info.edit'))
            ->assertOk()
            ->assertSee('Konfigurasi Aplikasi');
    }

    public function test_viewing_app_info_creates_default_row_when_missing(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.app-info.edit'))->assertOk();

        $this->assertDatabaseHas('app_info', ['app_name' => 'Melak']);
    }

    public function test_admin_can_update_app_info(): void
    {
        $this->actingAsAdmin();

        AppInfo::create([
            'app_name' => 'Melak',
            'developer' => 'Tim Melak',
        ]);

        $this->put(route('admin.app-info.update'), [
            'app_name' => 'Melak Siaga',
            'description' => 'Aplikasi deteksi penyakit tanaman.',
            'developer' => 'Tim Melak',
            'website' => 'https://ngingu.example.com',
            'support_email' => 'bantuan@ngingu.example.com',
        ])
            ->assertRedirect(route('admin.app-info.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('app_info', ['app_name' => 'Melak Siaga']);
    }

    public function test_update_app_info_requires_app_name_and_developer(): void
    {
        $this->actingAsAdmin();

        AppInfo::create(['app_name' => 'Melak', 'developer' => 'Tim Melak']);

        $this->put(route('admin.app-info.update'), [])
            ->assertSessionHasErrors(['app_name', 'developer']);
    }

    public function test_update_app_info_rejects_invalid_url_and_email(): void
    {
        $this->actingAsAdmin();

        AppInfo::create(['app_name' => 'Melak', 'developer' => 'Tim Melak']);

        $this->put(route('admin.app-info.update'), [
            'app_name' => 'Melak',
            'developer' => 'Tim Melak',
            'website' => 'not-a-url',
            'support_email' => 'not-an-email',
        ])
            ->assertSessionHasErrors(['website', 'support_email']);
    }

    public function test_admin_can_upload_apk_link(): void
    {
        $this->actingAsAdmin();

        $appInfo = AppInfo::create([
            'app_name' => 'Melak',
            'developer' => 'Tim Melak',
        ]);

        $this->post(route('admin.app-info.apk.upload'), [
            'apk_link' => 'https://melak.example.com/melak.apk',
            'apk_version' => '1.2.3',
        ])
            ->assertRedirect(route('admin.app-info.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('app_info', [
            'id' => $appInfo->id,
            'apk_path' => 'https://melak.example.com/melak.apk',
            'apk_version' => '1.2.3',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'app.apk_uploaded']);
    }

    public function test_upload_apk_requires_valid_url(): void
    {
        $this->actingAsAdmin();

        AppInfo::create(['app_name' => 'Melak', 'developer' => 'Tim Melak']);

        $this->post(route('admin.app-info.apk.upload'), [
            'apk_link' => 'not-a-url',
        ])
            ->assertSessionHasErrors('apk_link');
    }

    public function test_admin_can_remove_apk(): void
    {
        $this->actingAsAdmin();

        $appInfo = AppInfo::create([
            'app_name' => 'Melak',
            'developer' => 'Tim Melak',
            'apk_path' => 'https://melak.example.com/melak.apk',
            'apk_filename' => 'External Link',
            'apk_version' => '1.0.0',
        ]);

        $this->post(route('admin.app-info.apk.remove'))
            ->assertRedirect(route('admin.app-info.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('app_info', [
            'id' => $appInfo->id,
            'apk_path' => null,
            'apk_version' => null,
        ]);
    }
}
