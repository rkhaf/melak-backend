<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppTip;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * FR-06.5 / FR-09 — CRUD tips harian (app_tips).
 */
class TipAdminController extends Controller
{
    public function index(Request $request)
    {
        $tips = AppTip::query()
            ->when($request->filled('q'), fn ($q) => $q->where('content', 'like', "%{$request->q}%"))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = AppTip::select('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.tips.index', compact('tips', 'categories'));
    }

    public function create()
    {
        return view('admin.tips.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        $tip = AppTip::create($data);
        Audit::log('tip.created', 'Tips "'.Str::limit($tip->content, 60).'" ditambahkan.', $tip);

        return redirect()->route('admin.tips.index')->with('success', 'Tips berhasil ditambahkan.');
    }

    public function edit(AppTip $tip)
    {
        return view('admin.tips.edit', compact('tip'));
    }

    public function update(Request $request, AppTip $tip)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');

        $tip->update($data);
        Audit::log('tip.updated', 'Tips "'.Str::limit($tip->content, 60).'" diperbarui.', $tip);

        return redirect()->route('admin.tips.index')->with('success', 'Tips berhasil diperbarui.');
    }

    public function destroy(AppTip $tip)
    {
        $tip->delete();
        Audit::log('tip.deleted', 'Tips dihapus.');

        return redirect()->route('admin.tips.index')->with('success', 'Tips berhasil dihapus.');
    }

    public function toggle(AppTip $tip)
    {
        $tip->update(['is_active' => ! $tip->is_active]);
        Audit::log('tip.toggled', 'Tips '.($tip->is_active ? 'diaktifkan' : 'dinonaktifkan').'.', $tip);

        return back()->with('success', 'Tips '.($tip->is_active ? 'diaktifkan' : 'dinonaktifkan').'.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'content' => 'required|string|max:500',
            'category' => 'required|string|max:100',
            'published_on' => 'nullable|date',
        ], [
            'content.required' => 'Isi tips wajib diisi.',
            'content.max' => 'Isi tips maksimal 500 karakter.',
            'category.required' => 'Kategori tips wajib diisi.',
            'published_on.date' => 'Tanggal terbit tidak valid.',
        ]);
    }
}
