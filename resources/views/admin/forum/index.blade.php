@extends('layouts.admin')

@section('title', 'Moderasi Forum')
@section('header_title', 'Moderasi Forum')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Antrean Moderasi Forum</h3>
        <p class="text-sm text-gray-400">Setujui, tolak, hapus, pin, atau kunci post komunitas (FR-05.5.2)</p>
    </div>
</div>

<div class="flex flex-wrap items-center gap-2 mb-4">
    @foreach (['pending' => 'Menunggu', 'published' => 'Tayang', 'rejected' => 'Ditolak', 'locked' => 'Terkunci', 'all' => 'Semua'] as $key => $label)
        <a href="{{ route('admin.forum.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors {{ ($status ?? 'pending') === $key ? 'bg-[#0F767E] text-white shadow' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50' }}">
            {{ $label }}
            <span class="ml-1 {{ ($status ?? 'pending') === $key ? 'text-cyan-200' : 'text-gray-400' }}">({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach

    @if (($counts['reported'] ?? 0) > 0)
        <a href="{{ route('admin.reports.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition-colors">
            <i class="fa-solid fa-flag"></i> Laporan menunggu: {{ $counts['reported'] }}
        </a>
    @endif
</div>

<form method="GET" action="{{ route('admin.forum.index') }}" class="flex items-center gap-2 mb-5">
    <input type="hidden" name="status" value="{{ $status ?? 'pending' }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul atau isi post..."
           class="w-full md:w-80 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">
    <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>
</form>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3.5">Post</th>
                    <th class="px-5 py-3.5">Penulis</th>
                    <th class="px-5 py-3.5">Kategori</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-center">Laporan</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($posts as $post)
                    <tr class="hover:bg-gray-50 transition-colors {{ $post->is_pinned ? 'bg-amber-50/40' : '' }}">
                        <td class="px-5 py-4 max-w-[320px]">
                            <div class="flex items-center gap-2">
                                @if ($post->is_pinned)
                                    <i class="fa-solid fa-thumbtack text-amber-500" title="Dipin"></i>
                                @endif
                                <span class="font-bold text-[#1F2937]">{{ \Illuminate\Support\Str::limit($post->title, 70) }}</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $post->created_at->format('d/m/Y H:i') }} · {{ $post->comments_count }} komentar · {{ $post->votes_count }} vote
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-500">{{ $post->user?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-500 text-xs">{{ $post->category?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $badge = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'published' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-rose-100 text-rose-700',
                                    'locked' => 'bg-gray-200 text-gray-600',
                                ][$post->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badge }}">{{ $post->status }}</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if ($post->reports_count > 0)
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-600">{{ $post->reports_count }} laporan</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1.5">
                                @if ($post->status !== 'published')
                                    <form action="{{ route('admin.forum.approve', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-emerald-600 hover:bg-emerald-50 px-2.5 py-2 rounded-lg transition-colors" title="Setujui">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                                @if ($post->status !== 'rejected')
                                    <form action="{{ route('admin.forum.reject', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-amber-600 hover:bg-amber-50 px-2.5 py-2 rounded-lg transition-colors" title="Tolak">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.forum.pin', $post) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[#0F767E] hover:bg-teal-50 px-2.5 py-2 rounded-lg transition-colors" title="{{ $post->is_pinned ? 'Lepas pin' : 'Pin' }}">
                                        <i class="fa-solid fa-thumbtack"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.forum.lock', $post) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-500 hover:bg-gray-100 px-2.5 py-2 rounded-lg transition-colors" title="{{ $post->status === 'locked' ? 'Buka kunci' : 'Kunci thread' }}">
                                        <i class="fa-solid {{ $post->status === 'locked' ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.forum.destroy', $post) }}" method="POST" onsubmit="return confirm('Hapus post ini permanen?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 px-2.5 py-2 rounded-lg transition-colors" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-circle-check text-3xl block mb-2 text-emerald-400"></i>
                            Tidak ada post pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $posts->links() }}
    </div>
</div>
@endsection
