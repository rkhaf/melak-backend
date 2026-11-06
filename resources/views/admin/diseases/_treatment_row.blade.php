<div class="border border-gray-100 rounded-xl p-3 bg-gray-50/50" data-row>
    @isset($treatment['id'])
        <input type="hidden" name="treatments[{{ $i }}][id]" value="{{ $treatment['id'] }}">
    @endisset
    <div class="flex items-start gap-2">
        <div class="flex-1 space-y-2">
            <input type="text" name="treatments[{{ $i }}][title]" value="{{ $treatment['title'] ?? '' }}" placeholder="Judul langkah *"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            <textarea name="treatments[{{ $i }}][description]" rows="2" placeholder="Deskripsi"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">{{ $treatment['description'] ?? '' }}</textarea>
            <input type="number" name="treatments[{{ $i }}][duration_days]" min="0" max="999" value="{{ $treatment['duration_days'] ?? '' }}" placeholder="Durasi (hari)"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
        </div>
        <button type="button" onclick="this.closest('[data-row]').remove()" class="text-red-400 hover:text-red-600 p-1.5" title="Hapus baris">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
