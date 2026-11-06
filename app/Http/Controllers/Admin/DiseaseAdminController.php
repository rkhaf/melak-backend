<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use App\Models\DiseaseImage;
use App\Models\DiseasePrevention;
use App\Models\DiseaseTreatment;
use App\Support\Audit;
use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * FR-09.2.1 — CRUD ensiklopedia penyakit (termasuk treatment, prevention
 * & gambar yang tampil sebagai thumbnail di aplikasi, FR-03.1).
 */
class DiseaseAdminController extends Controller
{
    public function __construct(private readonly ImageStorage $storage) {}

    public function index(Request $request)
    {
        $diseases = Disease::with('images')
            ->withCount(['treatments', 'preventions'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%")
                        ->orWhere('name_en', 'like', "%{$request->q}%");
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_published', $request->status === 'published');
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.diseases.index', compact('diseases'));
    }

    public function create()
    {
        return view('admin.diseases.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['created_by'] = auth()->id();
        $data['is_published'] = $request->boolean('is_published');

        $disease = Disease::create($data);
        $this->syncGuides($disease, $request);
        $this->syncImages($disease, $request);

        Audit::log('disease.created', 'Penyakit "'.$disease->name.'" ditambahkan ke ensiklopedia.', $disease);

        return redirect()->route('admin.diseases.index')
            ->with('success', 'Penyakit "'.$disease->name.'" berhasil ditambahkan.');
    }

    public function edit(Disease $disease)
    {
        $disease->load(['treatments', 'preventions', 'images']);

        return view('admin.diseases.edit', compact('disease'));
    }

    public function update(Request $request, Disease $disease)
    {
        $data = $this->validateData($request, $disease);

        if ($disease->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $disease);
        }

        $data['is_published'] = $request->boolean('is_published');

        $disease->update($data);
        $this->syncGuides($disease, $request);
        $this->syncImages($disease, $request);

        Audit::log('disease.updated', 'Penyakit "'.$disease->name.'" diperbarui.', $disease);

        return redirect()->route('admin.diseases.index')
            ->with('success', 'Penyakit "'.$disease->name.'" berhasil diperbarui.');
    }

    public function destroy(Disease $disease)
    {
        $name = $disease->name;
        $disease->delete();
        Audit::log('disease.deleted', 'Penyakit "'.$name.'" dihapus dari ensiklopedia.');

        return redirect()->route('admin.diseases.index')
            ->with('success', 'Penyakit "'.$name.'" berhasil dihapus.');
    }

    private function validateData(Request $request, ?Disease $disease = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('diseases', 'name')->ignore($disease?->id)],
            'name_en' => 'nullable|string|max:255',
            'category' => 'required|in:bacteria,virus,parasite,fungi,other',
            'severity' => 'required|in:ringan,sedang,berat,kritis',
            'description' => 'nullable|string',
            'causes' => 'nullable|string',
            'symptoms' => 'nullable|string',
            'recovery_days_min' => 'nullable|integer|min:0|max:999',
            'recovery_days_max' => 'nullable|integer|min:0|max:999',
            'is_published' => 'sometimes|boolean',
            'treatments' => 'sometimes|array',
            'treatments.*.title' => 'required|string|max:255',
            'treatments.*.description' => 'nullable|string',
            'treatments.*.duration_days' => 'nullable|integer|min:0|max:999',
            'preventions' => 'sometimes|array',
            'preventions.*.description' => 'required|string',
            'images' => 'sometimes|array|max:6',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_captions' => 'sometimes|array',
            'image_captions.*' => 'nullable|string|max:255',
            'primary_image' => 'nullable|integer',
            'delete_images' => 'sometimes|array',
            'delete_images.*' => 'integer',
        ], [
            'name.required' => 'Nama penyakit wajib diisi.',
            'name.unique' => 'Nama penyakit sudah ada di ensiklopedia.',
            'category.required' => 'Kategori wajib dipilih.',
            'severity.required' => 'Tingkat keparahan wajib dipilih.',
            'treatments.*.title.required' => 'Judul langkah pengobatan wajib diisi.',
            'preventions.*.description.required' => 'Deskripsi pencegahan wajib diisi.',
            'images.max' => 'Maksimal 6 gambar per penyakit.',
            'images.*.image' => 'Setiap gambar harus berupa file gambar.',
            'images.*.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'images.*.max' => 'Ukuran gambar melebihi 5 MB.',
            'image_captions.*.max' => 'Keterangan gambar maksimal 255 karakter.',
        ]);
    }

    /**
     * Sinkronisasi langkah pengobatan & pencegahan dari input berulang.
     */
    private function syncGuides(Disease $disease, Request $request): void
    {
        $treatments = collect($request->input('treatments', []))
            ->filter(fn ($row) => trim((string) ($row['title'] ?? '')) !== '');

        $existingTreatmentIds = $disease->treatments()->pluck('id')->all();
        $keptTreatmentIds = [];

        foreach ($treatments->values() as $index => $row) {
            $attributes = [
                'step_order' => $index + 1,
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'duration_days' => $row['duration_days'] ?? null,
            ];

            if (! empty($row['id']) && in_array((int) $row['id'], $existingTreatmentIds, true)) {
                DiseaseTreatment::whereKey($row['id'])->update($attributes);
                $keptTreatmentIds[] = (int) $row['id'];
            } else {
                $keptTreatmentIds[] = $disease->treatments()->create($attributes)->id;
            }
        }

        DiseaseTreatment::where('disease_id', $disease->id)
            ->when($keptTreatmentIds !== [], fn ($q) => $q->whereNotIn('id', $keptTreatmentIds))
            ->delete();

        $preventions = collect($request->input('preventions', []))
            ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '');

        $existingPreventionIds = $disease->preventions()->pluck('id')->all();
        $keptPreventionIds = [];

        foreach ($preventions->values() as $index => $row) {
            $attributes = [
                'step_order' => $index + 1,
                'description' => $row['description'],
            ];

            if (! empty($row['id']) && in_array((int) $row['id'], $existingPreventionIds, true)) {
                DiseasePrevention::whereKey($row['id'])->update($attributes);
                $keptPreventionIds[] = (int) $row['id'];
            } else {
                $keptPreventionIds[] = $disease->preventions()->create($attributes)->id;
            }
        }

        DiseasePrevention::where('disease_id', $disease->id)
            ->when($keptPreventionIds !== [], fn ($q) => $q->whereNotIn('id', $keptPreventionIds))
            ->delete();
    }

    /**
     * Sinkronisasi gambar ensiklopedia (FR-03.1 — thumbnail di aplikasi):
     * upload baru, keterangan, pilihan gambar utama, dan hapus (row + file).
     */
    private function syncImages(Disease $disease, Request $request): void
    {
        $deleteIds = collect($request->input('delete_images', []))
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->values();

        if ($deleteIds->isNotEmpty()) {
            foreach ($disease->images()->whereIn('id', $deleteIds)->get() as $image) {
                if ($image->s3_key) {
                    try {
                        $this->storage->delete($image->s3_key);
                    } catch (\Throwable) {
                        // File sudah tidak ada — baris gambar tetap dihapus.
                    }
                }

                $image->delete();
            }
        }

        $order = (int) $disease->images()->max('order_index');

        foreach ($request->file('images', []) as $file) {
            $stored = $this->storage->storeDiseaseImage($file, $disease->id);

            $disease->images()->create([
                's3_key' => $stored['s3_key'],
                's3_url' => $stored['s3_url'],
                'caption' => null,
                'is_primary' => false,
                'order_index' => ++$order,
            ]);
        }

        $captions = (array) $request->input('image_captions', []);

        foreach ($disease->images()->get() as $image) {
            if (! array_key_exists($image->id, $captions)) {
                continue;
            }

            $caption = trim((string) $captions[$image->id]);
            $image->update(['caption' => $caption === '' ? null : $caption]);
        }

        $images = $disease->images()->orderBy('order_index')->orderBy('id')->get();

        if ($images->isEmpty()) {
            return;
        }

        // Tepat satu gambar utama → dipakai `thumbnail_url` di §5.1/§5.3.
        $chosen = $images->firstWhere('id', (int) $request->input('primary_image', 0))
            ?? $images->first(fn (DiseaseImage $image) => $image->is_primary)
            ?? $images->first();

        foreach ($images as $image) {
            if ($image->is_primary !== $image->is($chosen)) {
                $image->update(['is_primary' => $image->is($chosen)]);
            }
        }
    }

    private function uniqueSlug(string $name, ?Disease $ignore = null): string
    {
        $base = Str::slug($name) ?: 'penyakit';
        $slug = $base;
        $i = 2;

        while (Disease::where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
