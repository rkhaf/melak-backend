@php $faq = $faq ?? null; @endphp

<div class="space-y-5">
    <div>
        <label class="block text-sm font-bold text-gray-600 mb-1.5">Pertanyaan</label>
        <textarea name="question" rows="3" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" placeholder="contoh: Bagaimana cara melaporkan banjir?">{{ old('question', $faq?->question) }}</textarea>
        @error('question') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-bold text-gray-600 mb-1.5">Jawaban</label>
        <textarea name="answer" rows="6" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" placeholder="Jawaban lengkap untuk pertanyaan tersebut">{{ old('answer', $faq?->answer) }}</textarea>
        @error('answer') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-4 max-w-md">
        <div>
            <label class="block text-sm font-bold text-gray-600 mb-1.5">Urutan</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $faq?->sort_order) }}" min="0" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-600 mb-1.5">Status</label>
            <select name="is_active" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                <option value="1" @selected((bool) old('is_active', $faq?->is_active ?? true))>Aktif</option>
                <option value="0" @selected(!(bool) old('is_active', $faq?->is_active ?? true))>Nonaktif</option>
            </select>
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
            <i class="fa-solid fa-floppy-disk mr-1.5"></i> {{ isset($faq) ? 'Simpan Perubahan' : 'Simpan FAQ' }}
        </button>
        <a href="{{ route('admin.faqs.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-bold px-6 py-2.5 rounded-xl transition-colors">Batal</a>
    </div>
</div>
