@extends('layouts.admin')

@section('title', 'Model ML & Dataset')
@section('header_title', 'Model ML & Dataset')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Manajemen Model ML</h3>
        <p class="text-sm text-gray-400">Upload versi model, switch versi aktif, rollback, dan kelola dataset berlabel (FR-09.2.2 — FR-09.2.3)</p>
    </div>
    <form action="{{ route('admin.models.rollback') }}" method="POST" onsubmit="return confirm('Rollback ke versi model sebelumnya?')">
        @csrf
        <button type="submit" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <i class="fa-solid fa-rotate-left"></i> Rollback Versi Aktif
        </button>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
    <!-- Daftar versi -->
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider">Versi Model</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Versi</th>
                        <th class="px-5 py-3.5">Akurasi</th>
                        <th class="px-5 py-3.5">Diunggah</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($models as $model)
                        <tr class="hover:bg-gray-50 transition-colors {{ $model->is_active ? 'bg-emerald-50/40' : '' }}">
                            <td class="px-5 py-4">
                                <div class="font-bold text-[#1F2937]">{{ $model->version }}</div>
                                <div class="text-xs text-gray-400">{{ $model->filename }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-500">
                                {{ $model->accuracy_pct !== null ? $model->accuracy_pct.'%' : '—' }}
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-400">
                                {{ $model->created_at->format('d/m/Y H:i') }}<br>oleh {{ $model->uploader?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if ($model->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700">AKTIF</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    @unless ($model->is_active)
                                        <form action="{{ route('admin.models.activate', $model) }}" method="POST" onsubmit="return confirm('Jadikan versi ini aktif?')">
                                            @csrf
                                            <button type="submit" class="text-[#0F767E] hover:bg-teal-50 px-2.5 py-2 rounded-lg transition-colors" title="Aktifkan">
                                                <i class="fa-solid fa-bolt"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.models.destroy', $model) }}" method="POST" onsubmit="return confirm('Hapus versi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:bg-red-50 px-2.5 py-2 rounded-lg transition-colors" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 pr-2">dipakai</span>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-400">
                                <i class="fa-solid fa-microchip text-3xl block mb-2 text-gray-300"></i>
                                Belum ada versi model yang diunggah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upload model -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider mb-4">Upload Versi Baru</h4>
        <form action="{{ route('admin.models.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Versi *</label>
                    <input type="text" name="version" value="{{ old('version') }}" placeholder="mis. v1.2.0" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">File Model * (.h5/.onnx/.pt/.zip)</label>
                    <input type="file" name="model_file" required
                           class="w-full border border-dashed border-gray-300 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:text-[#0F767E] file:font-bold file:px-3 file:py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Akurasi (%)</label>
                    <input type="number" name="accuracy_pct" min="0" max="100" step="0.01" value="{{ old('accuracy_pct') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Label Penyakit (satu per baris)</label>
                    <textarea name="disease_labels" rows="4" placeholder="White Spot (Ich)&#10;Columnaris"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">{{ old('disease_labels') }}</textarea>
                </div>
            </div>
            <button type="submit" class="mt-5 w-full bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                <i class="fa-solid fa-upload"></i> Unggah Model
            </button>
        </form>
    </div>
</div>

<!-- ── Test permintaan ML ─────────────────────────────────────────────────── -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden" id="test-ml">
    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider">Test Permintaan ML</h4>
            <p class="text-xs text-gray-400 mt-1 max-w-3xl">
                Kirim foto sungguhan ke service prediksi (multipart, API.md §10.1), lalu periksa request yang
                dikirim, response mentah, hasil setelah lapisan adaptasi, dan apakah slug-nya menemukan penyakit
                di database (FR-10.11).
            </p>
        </div>
        <a href="{{ route('admin.models.status') }}"
           class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <i class="fa-solid fa-heart-pulse"></i> Cek Status Service
        </a>
    </div>

    <div class="p-5">
        @php($testInput = session('ml_test_input', []))

        <form action="{{ route('admin.models.test') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Foto uji (jpg/png/webp, maks 10 MB) *</label>
                    <input type="file" name="image" required accept=".jpg,.jpeg,.png,.webp"
                           class="w-full border border-dashed border-gray-300 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:text-[#0F767E] file:font-bold file:px-3 file:py-1.5">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Jenis ikan (opsional)</label>
                    <input type="text" name="fish_type" maxlength="100"
                           value="{{ old('fish_type', $testInput['fish_type'] ?? '') }}" placeholder="mis. Lele"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-gray-600 pb-2.5">
                        <input type="checkbox" name="gradcam" value="1"
                               @checked(old('gradcam', $testInput['gradcam'] ?? false))
                               class="rounded border-gray-300 text-[#0F767E] focus:ring-teal-500">
                        GradCAM
                    </label>
                    <p class="text-[11px] text-gray-400 -mt-2">tambah ±10 detik</p>
                </div>
                <div class="md:col-span-2">
                    <button type="submit"
                            class="w-full bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                        <i class="fa-solid fa-flask"></i> Jalankan Test
                    </button>
                </div>
            </div>
        </form>

        @error('image')
            <p class="mt-3 text-xs font-bold text-red-500"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
        @enderror

        {{-- Status service (GET /health) --}}
        @if (session('ml_health'))
            @php($health = session('ml_health'))
            <div class="mt-4 rounded-xl border px-4 py-3 text-sm {{ $health['ok'] ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 font-bold {{ $health['ok'] ? 'text-emerald-700' : 'text-red-600' }}">
                    <i class="fa-solid {{ $health['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                    {{ $health['ok'] ? 'Service sehat' : 'Service tidak terjangkau' }}
                    <span class="font-normal text-gray-500">
                        · <code class="text-xs">{{ $health['url'] }}</code>
                        · HTTP {{ $health['http_status'] ?? '—' }}
                        · {{ $health['elapsed_ms'] }} ms
                    </span>
                </div>
                @if (is_array($health['body'] ?? null))
                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                        <span><b>status:</b> {{ $health['body']['status'] ?? '—' }}</span>
                        <span><b>model:</b> {{ $health['body']['model_version'] ?? '—' }}</span>
                        <span><b>format:</b> {{ $health['body']['model_format'] ?? '—' }}</span>
                        <span><b>siap:</b> {{ ($health['body']['model_loaded'] ?? false) ? 'ya' : 'belum' }}</span>
                    </div>
                @endif
                @if (! empty($health['error']))
                    <p class="mt-1.5 text-xs text-red-600">{{ $health['error'] }}</p>
                @endif
            </div>
        @endif

        {{-- Hasil test request --}}
        @if (session('ml_test'))
            @php($t = session('ml_test'))
            <div class="mt-5 rounded-2xl border overflow-hidden {{ $t['ok'] ? 'border-teal-200' : 'border-red-200' }}">
                <div class="px-4 py-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 {{ $t['ok'] ? 'bg-teal-50' : 'bg-red-50' }}">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $t['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $t['ok'] ? 'BERHASIL' : 'GAGAL' }}
                    </span>
                    <span class="text-xs font-bold text-gray-600">{{ $t['endpoint'] }}</span>
                    <span class="text-xs text-gray-500">
                        @if ($t['driver'] === 'http')HTTP {{ $t['http_status'] ?? '—' }} · @endif{{ $t['elapsed_ms'] }} ms · driver <b>{{ $t['driver'] }}</b>
                    </span>
                    @if (! empty($t['url']))
                        <code class="text-[11px] text-gray-400">{{ $t['url'] }}</code>
                    @endif
                </div>

                <div class="p-4 space-y-4">
                    @if (! empty($t['note']))
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            <i class="fa-solid fa-triangle-exclamation"></i> {{ $t['note'] }}
                        </div>
                    @endif

                    {{-- 1. Request --}}
                    <div>
                        <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">1 · Request</h5>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <div class="text-gray-400">file</div>
                                <div class="font-bold text-gray-700 break-all">{{ $t['request']['filename'] }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <div class="text-gray-400">mime</div>
                                <div class="font-bold text-gray-700">{{ $t['request']['mime'] }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <div class="text-gray-400">ukuran</div>
                                <div class="font-bold text-gray-700">{{ number_format($t['request']['size_bytes'] / 1024, 1) }} KB</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <div class="text-gray-400">fish_type</div>
                                <div class="font-bold text-gray-700">{{ $t['request']['fish_type'] ?? '—' }}</div>
                            </div>
                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                <div class="text-gray-400">gradcam</div>
                                <div class="font-bold {{ $t['request']['gradcam'] ? 'text-[#0F767E]' : 'text-gray-500' }}">
                                    {{ $t['request']['gradcam'] ? 'true' : 'false' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Error --}}
                    @if (! empty($t['error']))
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <div class="text-xs font-bold text-red-700">
                                <i class="fa-solid fa-circle-xmark"></i>
                                {{ $t['error']['code'] }}
                                <span class="font-normal">· HTTP {{ $t['error']['status'] ?: 'tanpa respon' }}</span>
                            </div>
                            <p class="mt-1 text-sm text-red-800">{{ $t['error']['message'] }}</p>
                            @if ($t['error']['code'] === 'IMAGE_QUALITY_LOW')
                                <p class="mt-1 text-xs text-red-600">Foto ditolak oleh pemeriksaan kualitas (FR-10.12) — coba foto yang lebih terang/tajam.</p>
                            @endif
                        </div>
                    @endif

                    {{-- 3. Hasil lapisan adaptasi --}}
                    @if (! empty($t['normalized']))
                        @php($n = $t['normalized'])
                        <div>
                            <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">2 · Hasil Aplikasi (lapisan adaptasi)</h5>
                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 flex flex-wrap items-center gap-3">
                                    <div>
                                        <div class="text-lg font-extrabold text-[#1F2937]">{{ $n['disease_name'] }}</div>
                                        <div class="text-xs text-gray-400">
                                            slug <code>{{ $n['disease_slug'] }}</code>
                                            @if ($n['disease_label'])
                                                · label <code>{{ $n['disease_label'] }}</code>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-auto text-right">
                                        <div class="text-2xl font-extrabold {{ $n['confidence'] >= 60 ? 'text-emerald-600' : 'text-amber-500' }}">
                                            {{ number_format($n['confidence'], 1) }}%
                                        </div>
                                        <div class="text-[11px] text-gray-400">{{ $n['model_version'] }}</div>
                                    </div>
                                </div>

                                <div class="px-4 py-3 space-y-2">
                                    @php($sevClass = [
                                        'ringan' => 'bg-emerald-100 text-emerald-700',
                                        'sedang' => 'bg-amber-100 text-amber-700',
                                        'berat' => 'bg-orange-100 text-orange-700',
                                        'kritis' => 'bg-rose-100 text-rose-700',
                                    ][$n['severity']] ?? 'bg-gray-100 text-gray-600')
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="px-2.5 py-1 rounded-full font-bold {{ $sevClass }}">
                                            severity: {{ $n['severity'] ?? '—' }}
                                        </span>
                                        @if ($n['low_confidence_warning'])
                                            <span class="px-2.5 py-1 rounded-full font-bold bg-amber-100 text-amber-700">
                                                <i class="fa-solid fa-triangle-exclamation"></i> low_confidence_warning
                                            </span>
                                        @endif
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-bold">
                                            {{ $n['processing_time_ms'] }} ms (dilaporkan model)
                                        </span>
                                    </div>

                                    <div class="space-y-1.5 pt-1">
                                        @foreach ($n['top_predictions'] as $top)
                                            @php($pct = min(100, max(0, (float) $top['confidence'])))
                                            <div class="flex items-center gap-3 text-xs">
                                                <div class="w-40 shrink-0 text-gray-600 truncate" title="{{ $top['name'] }}">{{ $top['name'] }}</div>
                                                <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                                    <div class="h-full rounded-full {{ $loop->first ? 'bg-[#0F767E]' : 'bg-teal-300' }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <div class="w-14 text-right font-bold text-gray-700">{{ number_format($pct, 1) }}%</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if (! empty($n['gradcam_url']))
                                        <div class="pt-2">
                                            <div class="text-[11px] font-bold text-gray-400 uppercase mb-1.5">GradCAM (FR-10.8)</div>
                                            <img src="{{ $n['gradcam_url'] }}" alt="GradCAM"
                                                 class="max-h-56 rounded-lg border border-gray-200">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 4. Resolusi ke tabel diseases --}}
                    @if (! empty($t['disease']))
                        @php($d = $t['disease'])
                        <div>
                            <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">3 · Resolusi ke tabel diseases (FR-10.11)</h5>
                            @if ($d['found'])
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                    <i class="fa-solid fa-link"></i>
                                    <b>{{ $d['name'] }}</b> — id {{ $d['disease_id'] }},
                                    slug <code>{{ $d['slug'] }}</code>, severity {{ $d['severity'] }}
                                </div>
                            @else
                                <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                                    <i class="fa-solid fa-unlink"></i>
                                    {{ $d['hint'] }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- 2. Response mentah --}}
                    @if (! empty($t['raw']))
                        <div>
                            <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">4 · Response Mentah</h5>
                            <details class="group">
                                <summary class="cursor-pointer select-none text-xs font-bold text-[#0F767E] hover:underline">
                                    <i class="fa-solid fa-code"></i> Buka JSON ({{ $t['http_status'] }})
                                </summary>
                                <pre class="mt-2 max-h-96 overflow-auto rounded-xl bg-[#0F172A] text-emerald-300 text-[11px] leading-relaxed p-4">{{ json_encode($t['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Dataset -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider">Dataset Training Berlabel</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">File</th>
                        <th class="px-5 py-3.5">Label</th>
                        <th class="px-5 py-3.5">Penyakit</th>
                        <th class="px-5 py-3.5">Diunggah</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($datasets as $dataset)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 font-medium text-gray-700">{{ $dataset->filename }}</td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-teal-50 text-[#0F767E]">{{ $dataset->label }}</span>
                            </td>
                            <td class="px-5 py-4 text-gray-500 text-xs">{{ $dataset->disease?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-xs text-gray-400">{{ $dataset->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-right">
                                <form action="{{ route('admin.datasets.destroy', $dataset) }}" method="POST" onsubmit="return confirm('Hapus dataset ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 px-2.5 py-2 rounded-lg transition-colors" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-400">
                                <i class="fa-solid fa-images text-3xl block mb-2 text-gray-300"></i>
                                Belum ada dataset terunggah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $datasets->links() }}
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider mb-4">Upload Gambar Berlabel</h4>
        <form action="{{ route('admin.datasets.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Gambar (jpg/png/webp, maks 50 file)</label>
                    <input type="file" name="images[]" multiple required accept=".jpg,.jpeg,.png,.webp"
                           class="w-full border border-dashed border-gray-300 rounded-xl px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:text-[#0F767E] file:font-bold file:px-3 file:py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Kaitkan ke Penyakit</label>
                    <select name="disease_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
                        <option value="">— tanpa penyakit —</option>
                        @foreach (\App\Models\Disease::orderBy('name')->pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}" @selected(old('disease_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Label (wajib jika tanpa penyakit)</label>
                    <input type="text" name="label" value="{{ old('label') }}" placeholder="mis. white_spot"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                </div>
            </div>
            <button type="submit" class="mt-5 w-full bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                <i class="fa-solid fa-cloud-arrow-up"></i> Unggah Dataset
            </button>
        </form>
    </div>
</div>
@endsection
