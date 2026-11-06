<?php

namespace Tests\Feature\Admin;

use App\Models\AppSetting;
use App\Models\AppTip;

class TipsSettingsTest extends AdminTestCase
{
    public function test_tip_crud_and_toggle(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('admin.tips.store'), [
            'content' => 'Ganti air kolam 20% setiap minggu.',
            'category' => 'kualitas_air',
            'is_published' => null,
            'published_on' => now()->toDateString(),
            'is_active' => 1,
        ])->assertRedirect(route('admin.tips.index'));

        $tip = AppTip::where('content', 'Ganti air kolam 20% setiap minggu.')->firstOrFail();
        $this->assertTrue($tip->is_active);
        $this->assertSame(now()->toDateString(), $tip->published_on->toDateString());

        $this->assertDatabaseHas('audit_logs', ['action' => 'tip.created', 'model_id' => $tip->id]);

        $this->get(route('admin.tips.index'))->assertOk()->assertSee('Ganti air kolam 20%');

        $this->post(route('admin.tips.toggle', $tip))->assertRedirect();
        $this->assertFalse($tip->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tip.toggled', 'model_id' => $tip->id]);

        $this->put(route('admin.tips.update', $tip), [
            'content' => 'Tips yang sudah diperbarui.',
            'category' => 'pakan',
            'is_active' => 1,
        ])->assertRedirect(route('admin.tips.index'));

        $tip->refresh();
        $this->assertSame('Tips yang sudah diperbarui.', $tip->content);
        $this->assertSame('pakan', $tip->category);
        $this->assertTrue($tip->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tip.updated', 'model_id' => $tip->id]);

        $this->delete(route('admin.tips.destroy', $tip))->assertRedirect(route('admin.tips.index'));
        $this->assertNull($tip->fresh());
        $this->assertDatabaseHas('audit_logs', ['action' => 'tip.deleted']);
    }

    public function test_tip_store_requires_content(): void
    {
        $this->actingAsAdmin();

        $this->from(route('admin.tips.create'))
            ->post(route('admin.tips.store'), ['content' => '', 'category' => ''])
            ->assertSessionHasErrors(['content', 'category']);
    }

    public function test_settings_page_lists_seeded_settings_and_updates_values(): void
    {
        AppSetting::create(['key' => 'app.name', 'value' => 'Melak', 'group' => 'general', 'is_public' => true]);
        AppSetting::create(['key' => 'outbreak.threshold', 'value' => '5', 'group' => 'fitur', 'is_public' => false]);

        $admin = $this->actingAsAdmin();

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('app.name')
            ->assertSee('outbreak.threshold');

        $this->post(route('admin.settings.update'), [
            'values' => [
                'app.name' => 'Melak Fish Health',
                'outbreak.threshold' => '8',
                'unknown.key' => 'harus diabaikan',
            ],
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('app_settings', ['key' => 'app.name', 'value' => 'Melak Fish Health']);
        $this->assertDatabaseHas('app_settings', ['key' => 'outbreak.threshold', 'value' => '8']);
        $this->assertDatabaseMissing('app_settings', ['key' => 'unknown.key']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated', 'user_id' => $admin->id]);
    }

    public function test_settings_update_with_no_payload_still_succeeds(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.settings.update'), [])
            ->assertRedirect(route('admin.settings.index'));
    }
}
