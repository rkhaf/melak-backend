<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use App\Models\ForumPost;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Ensiklopedia penyakit (API.md §5, FR-03) — publik.
 */
class DiseaseController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ImageStorage $storage) {}

    /**
     * GET /api/diseases (API.md §5.1, FR-03.1.6).
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'category' => 'sometimes|in:bacteria,virus,parasite,fungi,other',
            'plant_type' => 'sometimes|string|max:64',
            'severity' => 'sometimes|in:ringan,sedang,berat,kritis',
            'q' => 'sometimes|string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Disease::query()
            ->published()
            ->with(['images', 'plantTypes'])
            ->when(! empty($data['category']), fn ($q) => $q->where('category', $data['category']))
            ->when(! empty($data['severity']), fn ($q) => $q->where('severity', $data['severity']))
            ->when(! empty($data['plant_type']), fn ($q) => $q->whereHas('plantTypes', fn ($f) => $f->where('plant_type', $data['plant_type'])))
            ->when(! empty($data['q']), fn ($q) => $this->applySearch($q, $data['q']))
            ->orderBy('name');

        $page = $query->paginate((int) ($data['per_page'] ?? 15))->withQueryString();

        return $this->paginated($page->through(fn (Disease $disease) => $this->listItem($disease)));
    }

    /**
     * GET /api/diseases/search (API.md §5.3).
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = Disease::query()
            ->published()
            ->with('images')
            ->where(fn ($q) => $this->applySearch($q, $data['q']))
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ['%'.$data['q'].'%'])
            ->orderBy('name');

        $page = $query->paginate((int) ($data['per_page'] ?? 15))->withQueryString();

        return $this->paginated($page->through(fn (Disease $disease) => [
            'id' => $disease->id,
            'name' => $disease->name,
            'slug' => $disease->slug,
            'category' => $disease->category,
            'severity' => $disease->severity,
            'thumbnail_url' => $this->primaryImage($disease)?->s3_url,
            'snippet' => Str::limit(strip_tags((string) $disease->symptoms), 120),
        ]));
    }

    /**
     * GET /api/diseases/{slug} (API.md §5.2, FR-03.2).
     */
    public function show(string $key): JsonResponse
    {
        // Menerima slug (kontrak API.md §5.2) atau id numerik (dipakai klien mobile).
        $disease = Disease::query()
            ->published()
            ->with(['images', 'plantTypes', 'treatments', 'preventions'])
            ->where(function ($query) use ($key) {
                $query->where('slug', $key);

                if (ctype_digit($key)) {
                    $query->orWhere('id', (int) $key);
                }
            })
            ->first();

        if (! $disease) {
            return $this->fail('Penyakit tidak ditemukan.', 404);
        }

        return $this->ok([
            'id' => $disease->id,
            'name' => $disease->name,
            'slug' => $disease->slug,
            'category' => $disease->category,
            'severity' => $disease->severity,
            'description' => $disease->description,
            'causes' => $disease->causes,
            'symptoms' => $this->lines($disease->symptoms),
            'plant_types' => $disease->plantTypes->pluck('plant_type')->values()->all(),
            'images' => $disease->images->map(fn ($image) => [
                'url' => $image->s3_url,
                'caption' => $image->caption,
                'is_primary' => (bool) $image->is_primary,
            ])->values()->all(),
            'treatments' => $disease->treatments->map(fn ($treatment) => [
                'step_order' => $treatment->step_order,
                'title' => $treatment->title,
                'description' => $treatment->description,
                'medicines' => $treatment->medicines ?? [],
                'duration_days' => $treatment->duration_days,
            ])->values()->all(),
            'preventions' => $disease->preventions->pluck('description')->values()->all(),
            'recovery_days' => [
                'min' => $disease->recovery_days_min,
                'max' => $disease->recovery_days_max,
            ],
            'related_forum_posts_count' => ForumPost::whereHas('scan.result', fn ($r) => $r->where('disease_id', $disease->id))->count(),
            'created_at' => $disease->created_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/diseases/{slug}/similar (API.md §5.4).
     */
    public function similar(string $slug): JsonResponse
    {
        $disease = Disease::query()->published()->with('plantTypes')->where('slug', $slug)->first();

        if (! $disease) {
            return $this->fail('Penyakit tidak ditemukan.', 404);
        }

        $plantTypes = $disease->plantTypes->pluck('plant_type')->all();

        $similar = Disease::query()
            ->published()
            ->where('id', '!=', $disease->id)
            ->where(fn ($q) => $q->where('category', $disease->category)
                ->orWhere(fn ($inner) => count($plantTypes) > 0 && $inner->whereHas('plantTypes', fn ($f) => $f->whereIn('plant_type', $plantTypes))))
            ->with('images')
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$disease->category])
            ->take(4)
            ->get();

        return $this->ok($similar->map(fn (Disease $item) => $this->listItem($item))->values()->all());
    }

    private function applySearch($query, string $q)
    {
        return $query->where(fn ($inner) => $inner
            ->where('name', 'like', "%{$q}%")
            ->orWhere('name_en', 'like', "%{$q}%")
            ->orWhere('description', 'like', "%{$q}%")
            ->orWhere('symptoms', 'like', "%{$q}%")
            ->orWhereHas('plantTypes', fn ($f) => $f->where('plant_type', 'like', "%{$q}%")));
    }

    private function listItem(Disease $disease): array
    {
        $primary = $this->primaryImage($disease);

        return [
            'id' => $disease->id,
            'name' => $disease->name,
            'slug' => $disease->slug,
            'category' => $disease->category,
            'severity' => $disease->severity,
            'thumbnail_url' => $primary?->s3_url,
            'plant_types' => $disease->plantTypes->pluck('plant_type')->values()->all(),
            'recovery_days' => [
                'min' => $disease->recovery_days_min,
                'max' => $disease->recovery_days_max,
            ],
        ];
    }

    /**
     * Gambar utama penyakit — dipakai sebagai thumbnail di aplikasi (§5.1/§5.3).
     * Relasi `images()` sudah diurutkan gambar utama dulu, jadi fallback
     * `first()` tetap memilih gambar utama bila penanda hilang.
     */
    private function primaryImage(Disease $disease)
    {
        return $disease->images->first(fn ($image) => $image->is_primary)
            ?? $disease->images->first();
    }

    private function lines(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text))));
    }
}
