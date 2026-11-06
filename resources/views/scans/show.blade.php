<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Scan — Melak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $severityClass = [
            'ringan' => 'bg-emerald-100 text-emerald-700',
            'sedang' => 'bg-amber-100 text-amber-700',
            'berat' => 'bg-orange-100 text-orange-700',
            'kritis' => 'bg-rose-100 text-rose-700',
        ][$disease?->severity] ?? 'bg-gray-100 text-gray-600';

        $statusClass = match ($scan->status) {
            'done' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-rose-100 text-rose-700',
            default => 'bg-sky-100 text-sky-700',
        };

        $statusLabel = match ($scan->status) {
            'done' => 'Selesai',
            'failed' => 'Gagal',
            default => 'Diproses',
        };

        $confidence = $result ? (float) $result->confidence_score : null;
        $symptoms = $result && $disease?->symptoms
            ? array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $disease->symptoms))))
            : [];
        $topPredictions = $result && is_array($result->top_predictions ?? null) ? $result->top_predictions : [];
    @endphp
</head>
<body class="bg-teal-50 min-h-screen px-4 py-8">
<div class="max-w-2xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="bg-[#0F767E] rounded-2xl shadow-sm p-6 text-white">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-white/15 flex items-center justify-center">
                <i class="fa-solid fa-plant text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-extrabold leading-tight">Melak — Hasil Scan Penyakit Tanaman</h1>
                <p class="text-xs text-white/80 mt-0.5">ID Scan: {{ $scan->id }}</p>
            </div>
        </div>
    </div>

    {{-- Status --}}
    @if ($scan->status === 'failed')
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl p-4 text-sm">
            <strong class="font-extrabold">Scan gagal.</strong>
            {{ $scan->error_message ?? 'Prediksi gagal diproses. Silakan coba lagi dengan foto yang lebih jelas.' }}
        </div>
    @elseif ($scan->status !== 'done')
        <div class="bg-sky-50 border border-sky-200 text-sky-700 rounded-2xl p-4 text-sm">
            Scan masih diproses. Buka kembali halaman ini beberapa saat lagi.
        </div>
    @endif

    @if ($result?->low_confidence_warning)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 text-sm">
            <strong class="font-extrabold">Confidence rendah (&lt; 60%).</strong>
            Hasil prediksi tidak pasti — periksa kembali kondisi tanaman atau lakukan scan ulang dengan foto yang lebih jelas.
        </div>
    @endif

    {{-- Hasil prediksi --}}
    @if ($result)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Hasil Prediksi</p>
                    <h2 class="text-xl font-extrabold text-gray-800">
                        {{ $disease?->name ?? ($topPredictions[0]['name'] ?? 'Tidak diketahui') }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    @if ($disease)
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $severityClass }}">{{ $disease->severity }}</span>
                    @endif
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>

            @if ($confidence !== null)
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs font-bold text-gray-500 mb-1.5">
                        <span>Confidence</span>
                        <span class="text-[#0F767E]">{{ number_format($confidence, 1) }}%</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-[#0F767E] rounded-full" style="width: {{ min(100, max(0, $confidence)) }}%"></div>
                    </div>
                </div>
            @endif

            @if ($topPredictions)
                <div class="mt-5">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Peringkat Prediksi</p>
                    <div class="space-y-1.5">
                        @foreach (array_slice($topPredictions, 0, 5) as $i => $pred)
                            <div class="flex items-center justify-between text-sm bg-gray-50 border border-gray-100 rounded-lg px-3 py-2">
                                <span class="text-gray-700"><span class="font-bold text-[#0F767E] mr-2">#{{ $i + 1 }}</span>{{ $pred['name'] ?? '-' }}</span>
                                <span class="font-bold text-gray-500">{{ $pred['confidence'] ?? '-' }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Informasi scan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Informasi Scan</p>
        <dl class="text-sm divide-y divide-gray-100">
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-gray-500">Tanggal scan</dt>
                <dd class="font-bold text-gray-800 text-right">{{ $scan->created_at?->format('d/m/Y H:i') }} WIB</dd>
            </div>
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-gray-500">Kebun / lahan</dt>
                <dd class="font-bold text-gray-800 text-right">{{ $scan->garden?->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-gray-500">Jenis tanaman</dt>
                <dd class="font-bold text-gray-800 text-right">{{ $scan->plant_type ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-gray-500">Deskripsi gejala</dt>
                <dd class="font-bold text-gray-800 text-right">{{ $scan->symptoms_desc ?? '-' }}</dd>
            </div>
            @if ($result)
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-gray-500">Waktu proses</dt>
                    <dd class="font-bold text-gray-800 text-right">{{ $result->processing_time_ms ?? '-' }} ms</dd>
                </div>
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-gray-500">Versi model</dt>
                    <dd class="font-bold text-gray-800 text-right">{{ $result->model_version ?? '-' }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Foto scan --}}
    @if ($scan->images->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Foto Scan</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($scan->images as $image)
                    <div class="space-y-2">
                        <a href="{{ $image->s3_url }}" target="_blank" rel="noopener">
                            <img src="{{ $image->s3_url }}" alt="Foto scan" loading="lazy"
                                 class="w-full h-32 object-cover rounded-xl border border-gray-200 bg-gray-50">
                        </a>
                        @if ($image->gradcam_url)
                            <a href="{{ $image->gradcam_url }}" target="_blank" rel="noopener">
                                <img src="{{ $image->gradcam_url }}" alt="GradCAM" loading="lazy"
                                     class="w-full h-32 object-cover rounded-xl border border-gray-200 bg-gray-50">
                            </a>
                            <p class="text-[11px] text-gray-400 text-center">GradCAM</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Panduan penyakit --}}
    @if ($disease)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">
            @if ($disease->description)
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Tentang Penyakit</p>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $disease->description }}</p>
                </div>
            @endif

            @if ($symptoms)
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Gejala</p>
                    <ul class="text-sm text-gray-600 space-y-1.5">
                        @foreach ($symptoms as $symptom)
                            <li class="flex gap-2">
                                <i class="fa-solid fa-circle text-[6px] text-[#0F767E] mt-2"></i>
                                <span>{{ $symptom }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Penanganan</p>
                @if ($disease->treatments->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada panduan penanganan.</p>
                @else
                    <ol class="text-sm text-gray-600 space-y-3">
                        @foreach ($disease->treatments as $treatment)
                            <li class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                                <span class="font-bold text-gray-800">{{ $loop->iteration }}. {{ $treatment->title }}</span>
                                @if ($treatment->description)
                                    <p class="mt-1 text-gray-600">{{ $treatment->description }}</p>
                                @endif
                                @if ($treatment->duration_days)
                                    <p class="mt-1 text-xs text-gray-400">Durasi: {{ $treatment->duration_days }} hari</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Pencegahan</p>
                @if ($disease->preventions->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada panduan pencegahan.</p>
                @else
                    <ul class="text-sm text-gray-600 space-y-1.5">
                        @foreach ($disease->preventions as $prevention)
                            <li class="flex gap-2">
                                <i class="fa-solid fa-shield-halved text-[#0F767E] mt-0.5"></i>
                                <span>{{ $prevention->description }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    {{-- CTA --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
        <a href="{{ config('app.url') }}"
           class="inline-block w-full bg-[#0F767E] hover:bg-teal-700 text-white text-sm font-bold px-4 py-3 rounded-xl transition-colors">
            Buka Melak
        </a>
        <p class="text-xs text-gray-400 mt-4 leading-relaxed">
            Scan dan riwayat lengkap tersedia di aplikasi Melak. Konsultasikan hasil ini dengan penyuluh pertanian
            untuk penanganan lanjutan.
        </p>
    </div>
</div>
</body>
</html>
