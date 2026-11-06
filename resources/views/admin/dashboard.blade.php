@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard Melak')

@section('content')
<!-- Welcome banner -->
<div class="mb-8 p-6 bg-gradient-to-r from-[#0F767E] to-[#0E8C94] text-white rounded-2xl shadow-md relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-2xl -translate-y-12 translate-x-12"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-extrabold tracking-tight">Halo, {{ Auth::user()->name }}!</h3>
            <p class="text-white/80 text-sm mt-1">Panel Administrator Melak — prediksi penyakit tanaman &amp; komunitas lahan.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.dashboard.export') }}" class="bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <button onclick="location.reload()" class="bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh Data
            </button>
        </div>
    </div>
</div>

<!-- Stats grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
        <div class="flex flex-col gap-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Scan Hari Ini</span>
            <span class="text-3xl font-black text-[#1F2937]">{{ number_format($stats['scans_today']) }}</span>
            <span class="text-[10px] {{ $stats['scans_today_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-semibold flex items-center gap-1 mt-1">
                <i class="fa-solid fa-{{ $stats['scans_today_pct'] >= 0 ? 'arrow-trend-up' : 'arrow-trend-down' }}"></i>
                {{ $stats['scans_today_pct'] >= 0 ? '+' : '' }}{{ $stats['scans_today_pct'] }}% dari kemarin
            </span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-[#E0F2F1] text-[#0F767E] flex items-center justify-center text-xl shadow-inner font-bold">
            <i class="fa-solid fa-plant-fins"></i>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
        <div class="flex flex-col gap-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Pengguna</span>
            <span class="text-3xl font-black text-[#1F2937]">{{ number_format($stats['total_users']) }}</span>
            <span class="text-[10px] text-gray-400 font-semibold mt-1">+{{ number_format($stats['users_today']) }} hari ini</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-[#E0F2F1] text-[#0F767E] flex items-center justify-center text-xl shadow-inner font-bold">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
        <div class="flex flex-col gap-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Menunggu Moderasi</span>
            <span class="text-3xl font-black text-[#1F2937]">{{ number_format($stats['pending_posts'] + $stats['reported_posts']) }}</span>
            <span class="text-[10px] text-gray-400 font-semibold mt-1">{{ number_format($stats['pending_posts']) }} post · {{ number_format($stats['reported_posts']) }} laporan</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-[#FFF7ED] text-[#F97316] flex items-center justify-center text-xl shadow-inner font-bold">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
        <div class="flex flex-col gap-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Model Aktif</span>
            <span class="text-2xl font-black text-[#1F2937]">{{ $stats['active_model'] }}</span>
            <span class="text-[10px] text-gray-400 font-semibold mt-1">{{ number_format($stats['active_diseases']) }} penyakit terbit</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-[#EDE9FE] text-[#7C3AED] flex items-center justify-center text-xl shadow-inner font-bold">
            <i class="fa-solid fa-brain"></i>
        </div>
    </div>
</div>

<!-- Charts + tables -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-extrabold text-[#1F2937]">Tren Scan 7 Hari Terakhir</h4>
            <span class="text-xs font-bold text-gray-400">{{ number_format($stats['scans_total']) }} total scan</span>
        </div>
        <canvas id="scanTrendChart" height="110"></canvas>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h4 class="font-extrabold text-[#1F2937] mb-4">Penyakit Terpopuler (7 hari)</h4>
        <div class="space-y-3">
            @forelse ($topDiseases as $disease)
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-semibold text-slate-700 truncate">{{ $disease->name }}</span>
                    <span class="text-xs font-bold text-[#0F767E] bg-[#E0F2F1] px-2 py-1 rounded-lg shrink-0">{{ $disease->total }} kasus</span>
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada data scan.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Latest forum posts -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h4 class="font-extrabold text-[#1F2937]">Post Forum Terbaru</h4>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3.5">Judul</th>
                    <th class="px-5 py-3.5">Penulis</th>
                    <th class="px-5 py-3.5">Kategori</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($latestPosts as $post)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="px-5 py-3.5 font-semibold text-slate-800">{{ \Illuminate\Support\Str::limit($post->title, 60) }}</td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $post->user?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $post->category?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            @php
                                $statusColor = ['pending' => 'bg-amber-100 text-amber-700', 'published' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-rose-100 text-rose-700', 'locked' => 'bg-slate-100 text-slate-600'][$post->status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $statusColor }}">{{ $post->status }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <span class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-gray-400 text-sm">Belum ada post forum.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('scanTrendChart'), {
        type: 'line',
        data: {
            labels: @json($chartDates),
            datasets: [{
                label: 'Scan',
                data: @json($chartTotals),
                borderColor: '#0F767E',
                backgroundColor: 'rgba(15, 118, 110, 0.12)',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#0F767E',
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endsection
