@extends('layouts.admin')

@section('title', 'Data Penyakit')
@section('header_title', 'Data Penyakit')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Ensiklopedia Penyakit</h3>
        <p class="text-sm text-gray-400">Kelola data penyakit tanaman beserta panduan pengobatan & pencegahan (FR-09.2.1)</p>
    </div>
    <a href="{{ route('admin.diseases.create') }}" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-colors flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Tambah Penyakit
    </a>
</div>

<form method="GET" action="{{ route('admin.diseases.index') }}" class="flex flex-wrap items-center gap-2 mb-5">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama penyakit..."
           class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
    <select name="category" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
        <option value="">Semua kategori</option>
        @foreach (['bacteria' => 'Bakteri', 'virus' => 'Virus', 'parasite' => 'Parasit', 'fungi' => 'Jamur', 'other' => 'Lainnya'] as $value => $label)
            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:outline-none">
        <option value="">Semua status</option>
        <option value="published" @selected(request('status') === 'published')>Terbit</option>
        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
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
                    <th class="px-5 py-3.5">Penyakit</th>
                    <th class="px-5 py-3.5">Kategori</th>
                    <th class="px-5 py-3.5 text-center">Severity</th>
                    <th class="px-5 py-3.5 text-center">Panduan</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($diseases as $disease)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 max-w-[300px]">
                            @php
                                $thumb = $disease->images->first(fn ($img) => $img->is_primary) ?? $disease->images->first();
                            @endphp
                            <div class="flex items-center gap-3">
                                @if ($thumb)
                                    <img src="{{ $thumb->s3_url }}" alt="" loading="lazy"
                                         class="w-10 h-10 rounded-lg object-cover border border-gray-200 bg-gray-50 shrink-0">
                                @else
                                    <div class="w-10 h-10 rounded-lg border border-dashed border-gray-200 bg-gray-50 flex items-center justify-center text-gray-300 shrink-0">
                                        <i class="fa-solid fa-bacterium"></i>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-bold text-[#1F2937] truncate">{{ $disease->name }}</div>
                                    <div class="text-xs text-gray-400 truncate">{{ $disease->name_en }} · /{{ $disease->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-500 text-xs capitalize">{{ $disease->category }}</td>
                        <td class="px-5 py-4 text-center">
                            @php
                                $sev = ['ringan' => 'bg-emerald-100 text-emerald-700', 'sedang' => 'bg-amber-100 text-amber-700', 'berat' => 'bg-orange-100 text-orange-700', 'kritis' => 'bg-rose-100 text-rose-700'][$disease->severity];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $sev }}">{{ $disease->severity }}</span>
                        </td>
                        <td class="px-5 py-4 text-center text-xs text-gray-500">
                            {{ $disease->treatments_count }} pengobatan<br>{{ $disease->preventions_count }} pencegahan
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $disease->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                {{ $disease->is_published ? 'Terbit' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.diseases.edit', $disease) }}" class="text-[#0F767E] hover:bg-teal-50 p-2 rounded-lg transition-colors" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.diseases.destroy', $disease) }}" method="POST" onsubmit="return confirm('Hapus penyakit ini beserta panduannya?')">
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
                            <i class="fa-solid fa-bacterium text-3xl block mb-2 text-gray-300"></i>
                            Belum ada data penyakit. <a href="{{ route('admin.diseases.create') }}" class="text-[#0F767E] font-bold">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $diseases->links() }}
    </div>
</div>
@endsection
