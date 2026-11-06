@extends('layouts.admin')

@section('title', 'Tips Harian')
@section('header_title', 'Tips Harian')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Tips Budidaya Ikan</h3>
        <p class="text-sm text-gray-400">Kelola konten tips yang dirotasi harian oleh sistem (FR-06.5)</p>
    </div>
    <a href="{{ route('admin.tips.create') }}" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-colors flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Tambah Tips
    </a>
</div>

<form method="GET" action="{{ route('admin.tips.index') }}" class="flex flex-wrap items-center gap-2 mb-5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari isi tips..."
           class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
    <select name="category" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
        <option value="">Semua kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
        @endforeach
    </select>
    <select name="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
        <option value="">Semua status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
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
                    <th class="px-5 py-3.5">Isi Tips</th>
                    <th class="px-5 py-3.5">Kategori</th>
                    <th class="px-5 py-3.5">Terbit</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tips as $tip)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 text-gray-400">{{ $tip->id }}</td>
                        <td class="px-5 py-4 max-w-[380px] text-gray-700">{{ \Illuminate\Support\Str::limit($tip->content, 120) }}</td>
                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-teal-50 text-[#0F767E]">{{ $tip->category }}</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-400">{{ $tip->published_on?->format('d/m/Y') ?? 'rotasi harian' }}</td>
                        <td class="px-5 py-4 text-center">
                            <form action="{{ route('admin.tips.toggle', $tip) }}" method="POST">
                                @csrf
                                <button type="submit" class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors {{ $tip->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <span class="inline-block w-4 h-4 transform bg-white rounded-full shadow transition-transform {{ $tip->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.tips.edit', $tip) }}" class="text-[#0F767E] hover:bg-teal-50 p-2 rounded-lg transition-colors" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.tips.destroy', $tip) }}" method="POST" onsubmit="return confirm('Hapus tips ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-lightbulb text-3xl block mb-2 text-gray-300"></i>
                            Belum ada tips. <a href="{{ route('admin.tips.create') }}" class="text-[#0F767E] font-bold">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $tips->links() }}
    </div>
</div>
@endsection
