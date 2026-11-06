@extends('layouts.admin')

@section('title', 'Audit Log')
@section('header_title', 'Audit Log Aktivitas')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Catatan Aktivitas Administrator</h3>
    <p class="text-sm text-gray-400">Jejak perubahan data dan aksi admin pada panel Melak</p>
</div>

<!-- Filter bar -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari aksi / deskripsi..." class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
        </div>
        <select name="action" class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
            <option value="">Semua Aksi</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ str_replace('_', ' ', $action) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" title="Dari tanggal">
        <input type="date" name="to" value="{{ request('to') }}" class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" title="Sampai tanggal">
        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        @if(request('q') || request('action') || request('from') || request('to'))
            <a href="{{ route('admin.audit-logs.index') }}" class="text-sm text-gray-400 hover:text-gray-600"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        @endif
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3.5">Aksi</th>
                    <th class="px-5 py-3.5">Deskripsi</th>
                    <th class="px-5 py-3.5">Oleh</th>
                    <th class="px-5 py-3.5">IP</th>
                    <th class="px-5 py-3.5">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="text-[11px] bg-[#0F767E]/10 text-[#0F767E] border border-[#0F767E]/20 px-2.5 py-1 rounded font-bold uppercase whitespace-nowrap">{{ str_replace('_', ' ', $log->action) }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 max-w-md">{{ $log->description }}</td>
                        <td class="px-5 py-3.5 text-gray-600">{{ $log->user?->name ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-gray-400 text-xs whitespace-nowrap">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-clock-rotate-left text-3xl block mb-2 text-gray-300"></i>
                            Belum ada catatan aktivitas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $logs->links() }}
    </div>
</div>
@endsection
