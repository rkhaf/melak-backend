@csrf

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 max-w-3xl">
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-gray-500 mb-1.5">Isi Tips *</label>
            <textarea name="content" rows="4" required
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">{{ old('content', $tip->content ?? '') }}</textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1.5">Kategori *</label>
                <input type="text" name="category" value="{{ old('category', $tip->category ?? 'umum') }}" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1.5">Tanggal Terbit (opsional — kosongkan untuk rotasi harian)</label>
                <input type="date" name="published_on" value="{{ old('published_on', isset($tip) && $tip->published_on ? $tip->published_on->format('Y-m-d') : '') }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tip->is_active ?? true))>
            Tips aktif
        </label>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
            <i class="fa-solid fa-floppy-disk"></i> Simpan
        </button>
        <a href="{{ route('admin.tips.index') }}" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-bold px-6 py-2.5 rounded-xl transition-colors">
            Batal
        </a>
    </div>
</div>
