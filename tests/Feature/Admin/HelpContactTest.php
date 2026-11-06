<?php

namespace Tests\Feature\Admin;

use App\Models\HelpContact;

class HelpContactTest extends AdminTestCase
{
    private array $validData = [
        'type' => 'phone',
        'title' => 'Posko Bantuan',
        'value' => '081234567890',
        'icon' => 'phone',
        'is_active' => true,
    ];

    public function test_admin_can_view_contacts_list(): void
    {
        $this->actingAsAdmin();

        HelpContact::create($this->validData);

        $this->get(route('admin.contacts.index'))
            ->assertOk()
            ->assertSee('Posko Bantuan');
    }

    public function test_admin_can_view_create_and_edit_pages(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.contacts.create'))->assertOk();

        $contact = HelpContact::create($this->validData);

        $this->get(route('admin.contacts.edit', $contact))
            ->assertOk()
            ->assertSee($contact->title);
    }

    public function test_admin_can_store_contact(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.contacts.store'), $this->validData)
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('help_contacts', ['title' => 'Posko Bantuan']);
    }

    public function test_store_contact_requires_type_title_and_value(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.contacts.store'), [])
            ->assertSessionHasErrors(['type', 'title', 'value']);
    }

    public function test_store_contact_rejects_invalid_type(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.contacts.store'), [...$this->validData, 'type' => 'telegram'])
            ->assertSessionHasErrors('type');
    }

    public function test_admin_can_update_contact(): void
    {
        $this->actingAsAdmin();

        $contact = HelpContact::create($this->validData);

        $this->put(route('admin.contacts.update', $contact), [...$this->validData, 'title' => 'Call Center'])
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('help_contacts', ['title' => 'Call Center']);
    }

    public function test_admin_can_toggle_contact(): void
    {
        $this->actingAsAdmin();

        $contact = HelpContact::create($this->validData);

        $this->post(route('admin.contacts.toggle', $contact))
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success');

        $this->assertFalse($contact->fresh()->is_active);
    }

    public function test_admin_can_reorder_contacts(): void
    {
        $this->actingAsAdmin();

        $first = HelpContact::create($this->validData);
        $second = HelpContact::create([...$this->validData, 'title' => 'WhatsApp']);

        $this->post(route('admin.contacts.reorder'), [
            'order' => [$second->id, $first->id],
        ])
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }

    public function test_admin_can_delete_contact(): void
    {
        $this->actingAsAdmin();

        $contact = HelpContact::create($this->validData);

        $this->delete(route('admin.contacts.destroy', $contact))
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('help_contacts', ['id' => $contact->id]);
    }
}
