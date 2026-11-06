@csrf

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider mb-4">Informasi Utama</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Nama Penyakit *</label>
                    <input type="text" name="name" value="{{ old('name', $disease->name ?? '') }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Nama Inggris</label>
                    <input type="text" name="name_en" value="{{ old('name_en', $disease->name_en ?? '') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Kategori *</label>
                    <select name="category" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
                        @foreach (['bacteria' => 'Bakteri', 'virus' => 'Virus', 'parasite' => 'Parasit', 'fungi' => 'Jamur', 'other' => 'Lainnya'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $disease->category ?? 'other') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Tingkat Keparahan *</label>
                    <select name="severity" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
                        @foreach (['ringan', 'sedang', 'berat', 'kritis'] as $value)
                            <option value="{{ $value }}" @selected(old('severity', $disease->severity ?? 'sedang') === $value)>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Pemulihan Minimum (hari)</label>
                    <input type="number" name="recovery_days_min" min="0" max="999" value="{{ old('recovery_days_min', $disease->recovery_days_min ?? '') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Pemulihan Maksimum (hari)</label>
                    <input type="number" name="recovery_days_max" min="0" max="999" value="{{ old('recovery_days_max', $disease->recovery_days_max ?? '') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-bold text-gray-500 mb-1.5">Deskripsi</label>
                <textarea name="description" rows="4" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">{{ old('description', $disease->description ?? '') }}</textarea>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-bold text-gray-500 mb-1.5">Penyebab</label>
                <textarea name="causes" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">{{ old('causes', $disease->causes ?? '') }}</textarea>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-bold text-gray-500 mb-1.5">Gejala</label>
                <textarea name="symptoms" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">{{ old('symptoms', $disease->symptoms ?? '') }}</textarea>
            </div>

            <label class="flex items-center gap-2 mt-4 text-sm text-gray-600">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $disease->is_published ?? true))>
                Terbitkan ke ensiklopedia
            </label>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider mb-1">Gambar Penyakit</h4>
            <p class="text-xs text-gray-400 mb-4">Dipakai sebagai gambar daftar &amp; gambar utama di ensiklopedia aplikasi (FR-03.1).</p>

            @php
                $diseaseImages = isset($disease) ? $disease->images->values() : collect();
                $currentPrimary = $diseaseImages->first(fn ($img) => $img->is_primary) ?? $diseaseImages->first();
                $primaryId = old('primary_image', $currentPrimary?->id);
            @endphp

            @if ($diseaseImages->isNotEmpty())
                <div class="space-y-3 mb-4">
                    @foreach ($diseaseImages as $image)
                        <div class="border border-gray-100 rounded-xl p-3 bg-gray-50/50 flex gap-3 items-start">
                            <img src="{{ $image->s3_url }}" alt="" loading="lazy"
                                 class="w-16 h-16 object-cover rounded-lg border border-gray-200 shrink-0 bg-white">
                            <div class="flex-1 min-w-0 space-y-2">
                                <input type="text" name="image_captions[{{ $image->id }}]"
                                       value="{{ old('image_captions.'.$image->id, $image->caption) }}"
                                       placeholder="Keterangan gambar (opsional)"
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
                                <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                    <input type="radio" name="primary_image" value="{{ $image->id }}"
                                           @checked((string) $primaryId === (string) $image->id)>
                                    Jadikan gambar utama
                                </label>
                            </div>
                            <label class="flex items-center gap-1 text-xs text-red-500 shrink-0 cursor-pointer"
                                   title="Gambar (dan filenya) dihapus saat form disimpan">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}"> Hapus
                            </label>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-xs text-gray-400 mb-4 bg-gray-50 border border-dashed border-gray-200 rounded-xl px-3 py-3 text-center">
                    Belum ada gambar — aplikasi menampilkan ikon default.
                </div>
            @endif

            <label class="block text-xs font-bold text-gray-500 mb-1.5">
                Tambah gambar (JPG/PNG/WEBP, maks 5 MB, maksimal 6 gambar)
            </label>
            <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp"
                   class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#0F767E] hover:file:bg-teal-100">
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider">Langkah Pengobatan</h4>
                <button type="button" onclick="addRow('treatments')" class="text-xs font-bold text-[#0F767E] hover:underline">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div id="treatments-rows" class="space-y-3">
                @php $treatments = old('treatments', isset($disease) ? $disease->treatments->toArray() : []); @endphp
                @forelse($treatments as $i => $treatment)
                    @include('admin.diseases._treatment_row', ['i' => $i, 'treatment' => $treatment])
                @empty
                    @include('admin.diseases._treatment_row', ['i' => 0, 'treatment' => []])
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider">Langkah Pencegahan</h4>
                <button type="button" onclick="addRow('preventions')" class="text-xs font-bold text-[#0F767E] hover:underline">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
            </div>
            <div id="preventions-rows" class="space-y-3">
                @php $preventions = old('preventions', isset($disease) ? $disease->preventions->toArray() : []); @endphp
                @forelse($preventions as $i => $prevention)
                    @include('admin.diseases._prevention_row', ['i' => $i, 'prevention' => $prevention])
                @empty
                    @include('admin.diseases._prevention_row', ['i' => 0, 'prevention' => []])
                @endforelse
            </div>
        </div>
    </div>
</div>

<template id="treatments-template">
    <div class="border border-gray-100 rounded-xl p-3 bg-gray-50/50" data-row>
        <div class="flex items-start gap-2">
            <div class="flex-1 space-y-2">
                <input type="text" name="treatments[__INDEX__][title]" placeholder="Judul langkah *"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
                <textarea name="treatments[__INDEX__][description]" rows="2" placeholder="Deskripsi"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"></textarea>
                <input type="number" name="treatments[__INDEX__][duration_days]" min="0" max="999" placeholder="Durasi (hari)"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
            </div>
            <button type="button" onclick="this.closest('[data-row]').remove()" class="text-red-400 hover:text-red-600 p-1.5" title="Hapus baris">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</template>

<template id="preventions-template">
    <div class="border border-gray-100 rounded-xl p-3 bg-gray-50/50" data-row>
        <div class="flex items-start gap-2">
            <textarea name="preventions[__INDEX__][description]" rows="2" placeholder="Deskripsi pencegahan *"
                      class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"></textarea>
            <button type="button" onclick="this.closest('[data-row]').remove()" class="text-red-400 hover:text-red-600 p-1.5" title="Hapus baris">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</template>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
        <i class="fa-solid fa-floppy-disk"></i> Simpan
    </button>
    <a href="{{ route('admin.diseases.index') }}" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-bold px-6 py-2.5 rounded-xl transition-colors">
        Batal
    </a>
</div>

<script>
    let treatmentIndex = {{ is_array($treatments) ? count($treatments) : 0 }};
    let preventionIndex = {{ is_array($preventions) ? count($preventions) : 0 }};

    function addRow(type) {
        const container = document.getElementById(type + '-rows');
        const template = document.getElementById(type + '-template');
        const html = template.innerHTML.replaceAll('__INDEX__', type === 'treatments' ? treatmentIndex++ : preventionIndex++);
        container.insertAdjacentHTML('beforeend', html);
    }
</script>
