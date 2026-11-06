@extends('layouts.admin')

@section('title', 'Laporan Forum')
@section('header_title', 'Laporan Forum')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Laporan Konten Forum</h3>
    <p class="text-sm text-gray-400">Review laporan pengguna dan tindak lanjuti konten yang dilaporkan (NFR-02.7)</p>
</div>

<div class="flex flex-wrap items-center gap-2 mb-4">
    @foreach (['pending' => 'Menunggu', 'reviewed' => 'Diterima', 'dismissed' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
        <a href="{{ route('admin.reports.index', array_filter(['status' => $key, 'reason' => request('reason')])) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors {{ ($status ?? 'pending') === $key ? 'bg-[#0F767E] text-white shadow' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
            {{ $label }}
            <span class="ml-1 {{ ($status ?? 'pending') === $key ? 'text-cyan-200' : 'text-gray-400' }}">({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2 mb-5">
    <input type="hidden" name="status" value="{{ $status ?? 'pending' }}">
    <select name="reason" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40">
        <option value="">Semua alasan</option>
        @foreach ($reasons as $reason)
            <option value="{{ $reason }}" @selected(request('reason') === $reason)>{{ $reason }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
        <i class="fa-solid fa-filter"></i> Filter
    </button>
</form>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3.5 w-14">#</th>
                    <th class="px-5 py-3.5">Pelapor</th>
                    <th class="px-5 py-3.5">Konten</th>
                    <th class="px-5 py-3.5">Alasan</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 text-gray-400">{{ $report->id }}</td>
                        <td class="px-5 py-4">
                            <div class="font-semibold text-[#1F2937]">{{ $report->reporter?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-400">{{ $report->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-5 py-4 max-w-[300px]">
                            @if ($report->reportable)
                                <span class="font-medium text-gray-700">{{ \Illuminate\Support\Str::limit($report->reportable->title ?? $report->reportable->content_html ?? 'Konten', 70) }}</span>
                                <div class="text-xs text-gray-400">Tipe: {{ $report->reportable_type }}</div>
                            @else
                                <span class="text-gray-300 italic">Konten sudah dihapus</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-orange-100 text-orange-700">{{ $report->reason }}</span>
                            @if ($report->notes)
                                <div class="text-xs text-gray-400 mt-1 max-w-[220px]">{{ \Illuminate\Support\Str::limit($report->notes, 80) }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $badge = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'reviewed' => 'bg-emerald-100 text-emerald-700',
                                    'dismissed' => 'bg-gray-200 text-gray-600',
                                ][$report->status];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badge }}">{{ $report->status }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if ($report->status === 'pending')
                                <div class="flex items-center justify-end gap-1.5">
                                    <form action="{{ route('admin.reports.review', $report) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-emerald-600 hover:bg-emerald-50 px-2.5 py-2 rounded-lg transition-colors" title="Terima laporan">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.reports.dismiss', $report) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-gray-500 hover:bg-gray-100 px-2.5 py-2 rounded-lg transition-colors" title="Tolak laporan">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">Diproses oleh {{ $report->reviewer?->name ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-flag text-3xl block mb-2 text-gray-300"></i>
                            Tidak ada laporan pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $reports->links() }}
    </div>
</div>
@endsection
