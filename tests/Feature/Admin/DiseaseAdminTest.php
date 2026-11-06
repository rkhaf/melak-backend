<?php

namespace Tests\Feature\Admin;

use App\Models\Disease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DiseaseAdminTest extends AdminTestCase
{
    public function test_store_creates_disease_with_guides_and_audit(): void
    {
        $admin = $this->actingAsAdmin();

        $this->post(route('admin.diseases.store'), [
            'name' => 'White Spot (Ich)',
            'name_en' => 'White Spot',
            'category' => 'parasite',
            'severity' => 'berat',
            'description' => 'Bintik putih pada tubuh ikan.',
            'causes' => 'Protozoa Ichthyophthirius multifiliis.',
            'symptoms' => 'Bintik putih, ikan menggosokkan tubuh.',
            'recovery_days_min' => 5,
            'recovery_days_max' => 14,
            'is_published' => 1,
            'treatments' => [
                ['title' => 'Naikkan suhu', 'description' => 'Suhu 30°C', 'duration_days' => 5],
                ['title' => 'Garam', 'description' => '3 ppt', 'duration_days' => 3],
            ],
            'preventions' => [
                ['description' => 'Karantina ikan baru.'],
            ],
        ])->assertRedirect(route('admin.diseases.index'));

        $disease = Disease::where('slug', 'white-spot-ich')->firstOrFail();
        $this->assertSame('parasite', $disease->category);
        $this->assertSame($admin->id, $disease->created_by);
        $this->assertTrue($disease->is_published);
        $this->assertCount(2, $disease->treatments);
        $this->assertCount(1, $disease->preventions);
        $this->assertSame(1, $disease->treatments()->first()->step_order);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'disease.created',
            'model_id' => $disease->id,
        ]);

        $this->get(route('admin.diseases.edit', $disease))
            ->assertOk()
            ->assertSee('White Spot (Ich)')
            ->assertSee('Naikkan suhu');
    }

    public function test_store_requires_name_and_valid_enums(): void
    {
        $this->actingAsAdmin();

        $this->from(route('admin.diseases.create'))
            ->post(route('admin.diseases.store'), ['name' => ''])
            ->assertSessionHasErrors(['name', 'category', 'severity']);

        $this->post(route('admin.diseases.store'), [
            'name' => 'X',
            'category' => 'bogus',
            'severity' => 'bogus',
        ])->assertSessionHasErrors(['category', 'severity']);

        $this->assertSame(0, Disease::count());
    }

    public function test_update_syncs_guides_and_regenerates_slug(): void
    {
        $admin = $this->actingAsAdmin();
        $disease = Disease::factory()->create(['name' => 'Lama', 'slug' => 'lama']);

        $keep = $disease->treatments()->create(['step_order' => 1, 'title' => 'Langkah Lama']);
        $disease->preventions()->create(['step_order' => 1, 'description' => 'Pencegahan Lama']);

        $this->put(route('admin.diseases.update', $disease), [
            'name' => 'Columnaris',
            'category' => 'bacteria',
            'severity' => 'sedang',
            'is_published' => 1,
            'treatments' => [
                ['id' => $keep->id, 'title' => 'Langkah Baru', 'description' => 'Diperbarui'],
                ['title' => 'Langkah Tambahan'],
            ],
            'preventions' => [],
        ])->assertRedirect(route('admin.diseases.index'));

        $disease->refresh();

        $this->assertSame('Columnaris', $disease->name);
        $this->assertSame('columnaris', $disease->slug);
        $this->assertCount(2, $disease->treatments);
        $this->assertSame('Langkah Baru', $keep->refresh()->title);
        $this->assertCount(0, $disease->preventions);

        $this->assertDatabaseHas('audit_logs', ['action' => 'disease.updated', 'model_id' => $disease->id]);
    }

    public function test_duplicate_name_is_rejected_on_store(): void
    {
        Disease::factory()->create(['name' => 'Velvet']);
        $this->actingAsAdmin();

        $this->post(route('admin.diseases.store'), [
            'name' => 'Velvet',
            'category' => 'parasite',
            'severity' => 'sedang',
        ])->assertSessionHasErrors('name');
    }

    public function test_destroy_removes_disease_and_logs_audit(): void
    {
        $admin = $this->actingAsAdmin();
        $disease = Disease::factory()->create();
        $disease->treatments()->create(['step_order' => 1, 'title' => 'X']);

        $this->delete(route('admin.diseases.destroy', $disease))
            ->assertRedirect(route('admin.diseases.index'));

        $this->assertDatabaseMissing('diseases', ['id' => $disease->id]);
        $this->assertDatabaseMissing('disease_treatments', ['disease_id' => $disease->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'disease.deleted', 'user_id' => $admin->id]);
    }

    public function test_images_can_be_uploaded_captioned_and_deleted(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $disease = Disease::factory()->create(['name' => 'Aeromonas', 'slug' => 'aeromonas-img']);

        // 1) Upload dua gambar dari form edit.
        $this->put(route('admin.diseases.update', $disease), [
            'name' => 'Aeromonas',
            'category' => 'bacteria',
            'severity' => 'berat',
            'is_published' => 1,
            'images' => [
                UploadedFile::fake()->image('satu.jpg', 400, 300),
                UploadedFile::fake()->image('dua.png', 400, 300),
            ],
        ])->assertRedirect(route('admin.diseases.index'));

        $images = $disease->images()->orderBy('id')->get();
        $this->assertCount(2, $images);
        $this->assertSame(1, $images->where('is_primary', true)->count(), 'Tepat satu gambar utama');
        Storage::disk('public')->assertExists($images->first()->s3_key);
        $this->assertStringContainsString('/diseases/'.$disease->id.'/', $images->first()->s3_url);

        // 2) Ubah keterangan + pindahkan gambar utama + hapus satu gambar.
        [$first, $second] = [$images->first(), $images->last()];

        $this->put(route('admin.diseases.update', $disease), [
            'name' => 'Aeromonas',
            'category' => 'bacteria',
            'severity' => 'berat',
            'is_published' => 1,
            'image_captions' => [$second->id => 'Gejala khas'],
            'primary_image' => $second->id,
            'delete_images' => [$first->id],
        ])->assertRedirect(route('admin.diseases.index'));

        $this->assertDatabaseMissing('disease_images', ['id' => $first->id]);
        Storage::disk('public')->assertMissing($first->s3_key);

        $second->refresh();
        $this->assertSame('Gejala khas', $second->caption);
        $this->assertTrue($second->is_primary);
        $this->assertSame(1, $disease->images()->where('is_primary', true)->count());
    }

    public function test_image_upload_rejects_non_image_and_too_many_files(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $this->from(route('admin.diseases.create'))
            ->post(route('admin.diseases.store'), [
                'name' => 'Velvet',
                'category' => 'parasite',
                'severity' => 'sedang',
                'images' => [UploadedFile::fake()->create('dokumen.pdf', 100)],
            ])
            ->assertSessionHasErrors('images.0');

        $this->post(route('admin.diseases.store'), [
            'name' => 'Velvet',
            'category' => 'parasite',
            'severity' => 'sedang',
            'images' => collect(range(1, 7))
                ->map(fn ($i) => UploadedFile::fake()->image("g{$i}.jpg", 60, 60))
                ->all(),
        ])->assertSessionHasErrors('images');

        $this->assertSame(0, Disease::count());
    }

    public function test_index_filters_by_search_and_category(): void
    {
        Disease::factory()->create(['name' => 'Aeromonas Secreta', 'name_en' => 'Bacterial Hemorrhage', 'category' => 'bacteria']);
        Disease::factory()->create(['name' => 'Saprolegnia', 'name_en' => 'Water Mold Infection', 'category' => 'fungi']);

        $admin = $this->actingAsAdmin();

        $this->actingAs($admin)->get(route('admin.diseases.index', ['q' => 'Aeromonas']))
            ->assertOk()
            ->assertSee('Aeromonas Secreta')
            ->assertDontSee('Saprolegnia');

        $this->actingAs($admin)->get(route('admin.diseases.index', ['category' => 'fungi']))
            ->assertSee('Saprolegnia')
            ->assertDontSee('Aeromonas Secreta');
    }
}
