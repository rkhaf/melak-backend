<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;

class FaqCrudTest extends AdminTestCase
{
    private array $validData = [
        'question' => 'Apa yang harus dilakukan saat tanaman sakit?',
        'answer' => 'Segera periksa tanaman yang sakit dan konsultasikan gejalanya di forum Melak.',
        'is_active' => true,
    ];

    public function test_admin_can_view_faqs_list(): void
    {
        $this->actingAsAdmin();

        Faq::create($this->validData);

        $this->get(route('admin.faqs.index'))
            ->assertOk()
            ->assertSee('Apa yang harus dilakukan saat tanaman sakit?');
    }

    public function test_admin_can_view_create_and_edit_pages(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.faqs.create'))->assertOk();

        $faq = Faq::create($this->validData);

        $this->get(route('admin.faqs.edit', $faq))
            ->assertOk()
            ->assertSee($faq->question);
    }

    public function test_admin_can_store_faq(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.faqs.store'), $this->validData)
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faqs', ['question' => 'Apa yang harus dilakukan saat tanaman sakit?']);
    }

    public function test_store_faq_requires_question_and_answer(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.faqs.store'), [])
            ->assertSessionHasErrors(['question', 'answer']);
    }

    public function test_admin_can_update_faq(): void
    {
        $this->actingAsAdmin();

        $faq = Faq::create($this->validData);

        $this->put(route('admin.faqs.update', $faq), [...$this->validData, 'question' => 'FAQ diperbarui'])
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faqs', ['question' => 'FAQ diperbarui']);
    }

    public function test_admin_can_toggle_faq(): void
    {
        $this->actingAsAdmin();

        $faq = Faq::create($this->validData);

        $this->post(route('admin.faqs.toggle', $faq))
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertFalse($faq->fresh()->is_active);
    }

    public function test_admin_can_delete_faq(): void
    {
        $this->actingAsAdmin();

        $faq = Faq::create($this->validData);

        $this->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
