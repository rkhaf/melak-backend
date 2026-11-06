<div class="border border-gray-100 rounded-xl p-3 bg-gray-50/50" data-row>
    @isset($prevention['id'])
        <input type="hidden" name="preventions[{{ $i }}][id]" value="{{ $prevention['id'] }}">
    @endisset
    <div class="flex items-start gap-2">
        <textarea name="preventions[{{ $i }}][description]" rows="2" placeholder="Deskripsi pencegahan *"
                  class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">{{ $prevention['description'] ?? '' }}</textarea>
        <button type="button" onclick="this.closest('[data-row]').remove()" class="text-red-400 hover:text-red-600 p-1.5" title="Hapus baris">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
