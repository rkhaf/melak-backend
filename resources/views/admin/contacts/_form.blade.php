@php $contact = $contact ?? null; @endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-bold text-gray-600 mb-1.5">Judul</label>
            <input type="text" name="title" value="{{ old('title', $contact?->title) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" placeholder="contoh: Telepon Darurat">
            @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-600 mb-1.5">Tipe</label>
            <select name="type" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @foreach(['phone', 'email', 'website', 'whatsapp', 'other'] as $type)
                    <option value="{{ $type }}" @selected(old('type', $contact?->type) === $type)>{{ \App\Support\UiHelper::contactType($type) }}</option>
                @endforeach
            </select>
            @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-600 mb-1.5">Nilai Kontak</label>
            <input type="text" name="value" value="{{ old('value', $contact?->value) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" placeholder="contoh: 0800-1234-5678">
            @error('value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-600 mb-2">Pilih Ikon Kontak</label>
            <input type="hidden" name="icon" id="selected-icon" value="{{ old('icon', $contact?->icon ?? 'phone') }}">
            
            <div class="grid grid-cols-4 gap-3">
                @foreach([
                    'phone' => 'Telepon',
                    'envelope' => 'Email',
                    'globe' => 'Website',
                    'whatsapp' => 'WhatsApp',
                    'stethoscope' => 'Dokter Hewan',
                    'truck-medical' => 'Ambulans',
                    'circle-exclamation' => 'Bahaya',
                    'location-dot' => 'Kantor'
                ] as $value => $label)
                    @php
                        $isSelected = old('icon', $contact?->icon ?? 'phone') == $value;
                    @endphp
                    <button type="button" 
                            onclick="selectContactIcon('{{ $value }}')" 
                            id="icon-btn-{{ $value }}"
                            class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition-all duration-150 hover:bg-slate-50 icon-selector-btn {{ $isSelected ? 'border-[#0F767E] bg-[#E0F2F1] text-[#0F767E] ring-2 ring-[#0F767E]/30 font-bold' : 'border-gray-200 text-gray-500 hover:text-slate-700' }}"
                            title="{{ $label }}">
                        @php
                            $btnPrefix = ($value === 'whatsapp') ? 'fa-brands' : 'fa-solid';
                        @endphp
                        <i class="{{ $btnPrefix }} fa-{{ $value }} text-lg"></i>
                        <span class="text-[9px] mt-1.5 font-medium truncate w-full">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            @error('icon') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $contact?->sort_order) }}" min="0" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Status</label>
                <select name="is_active" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                    <option value="1" @selected((bool) old('is_active', $contact?->is_active ?? true))>Aktif</option>
                    <option value="0" @selected(!(bool) old('is_active', $contact?->is_active ?? true))>Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> {{ isset($contact) ? 'Simpan Perubahan' : 'Simpan Kontak' }}
            </button>
            <a href="{{ route('admin.contacts.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-bold px-6 py-2.5 rounded-xl transition-colors">Batal</a>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6">
            <h4 class="text-sm font-extrabold text-[#1F2937] mb-4"><i class="fa-solid fa-eye text-[#0F767E] mr-2"></i> Pratinjau Kartu Kontak</h4>
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4 shadow-sm max-w-xs">
                <div class="w-12 h-12 rounded-xl bg-[#E0F2F1] text-[#0F767E] flex items-center justify-center text-xl shrink-0">
                    @php
                        $icon = old('icon', $contact?->icon ?? 'phone');
                        $prefix = ($icon === 'whatsapp') ? 'fa-brands' : 'fa-solid';
                    @endphp
                    <i id="preview-icon" class="{{ $prefix }} fa-{{ $icon }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p id="preview-type" class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">{{ old('type', $contact?->type ?? 'phone') }}</p>
                    <p id="preview-title" class="text-sm font-bold text-[#1F2937] truncate">{{ old('title', $contact?->title ?? 'Telepon') }}</p>
                    <p id="preview-value" class="text-xs text-gray-500 truncate">{{ old('value', $contact?->value ?? '-') }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">Pratinjau menyesuaikan secara otomatis dengan isian form.</p>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fields = ['title', 'value', 'type'];

        fields.forEach(function (name) {
            const el = document.querySelector('[name="' + name + '"]');
            if (el) {
                el.addEventListener('input', function() {
                    updateTextPreview(name, el.value);
                });
                el.addEventListener('change', function() {
                    updateTextPreview(name, el.value);
                });
            }
        });

        function updateTextPreview(name, value) {
            const target = document.getElementById('preview-' + name);
            if (target) {
                target.innerText = value || (name === 'value' ? '-' : (name === 'title' ? 'Telepon' : 'phone'));
            }
        }
    });

    function selectContactIcon(iconValue) {
        // Set hidden input value
        document.getElementById('selected-icon').value = iconValue;

        // Reset style of all buttons
        document.querySelectorAll('.icon-selector-btn').forEach(btn => {
            btn.classList.remove('border-[#0F767E]', 'bg-[#E0F2F1]', 'text-[#0F767E]', 'ring-2', 'ring-[#0F767E]/30', 'font-bold');
            btn.classList.add('border-gray-200', 'text-gray-500', 'hover:text-slate-700');
        });

        // Highlight selected button
        const selectedBtn = document.getElementById('icon-btn-' + iconValue);
        if (selectedBtn) {
            selectedBtn.classList.remove('border-gray-200', 'text-gray-500', 'hover:text-slate-700');
            selectedBtn.classList.add('border-[#0F767E]', 'bg-[#E0F2F1]', 'text-[#0F767E]', 'ring-2', 'ring-[#0F767E]/30', 'font-bold');
        }

        // Update real-time preview card icon
        const iconBox = document.querySelector('#preview-icon');
        if (iconBox) {
            const prefix = (iconValue === 'whatsapp') ? 'fa-brands' : 'fa-solid';
            iconBox.className = prefix + ' fa-' + iconValue;
        }
    }
</script>
@endsection
