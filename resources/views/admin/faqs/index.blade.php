@extends('layouts.admin')

@section('title', 'FAQ')
@section('header_title', 'FAQ')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Daftar FAQ</h3>
        <p class="text-sm text-gray-400">Pertanyaan yang sering diajukan di halaman Bantuan aplikasi</p>
    </div>
    <a href="{{ route('admin.faqs.create') }}" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-colors flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Tambah FAQ
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3.5 w-16">#</th>
                    <th class="px-5 py-3.5">Pertanyaan</th>
                    <th class="px-5 py-3.5">Jawaban</th>
                    <th class="px-5 py-3.5">Urutan</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($faqs as $faq)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 text-gray-400">{{ $faqs->firstItem() + $loop->index }}</td>
                        <td class="px-5 py-4 font-bold text-[#1F2937] max-w-[300px]">{{ Str::limit($faq->question, 60) }}</td>
                        <td class="px-5 py-4 text-gray-500 max-w-[300px]">{{ Str::limit($faq->answer, 80) }}</td>
                        <td class="px-5 py-4 text-gray-400 text-xs">{{ $faq->sort_order }}</td>
                        <td class="px-5 py-4">
                            <form action="{{ route('admin.faqs.toggle', $faq) }}" method="POST">
                                @csrf
                                <button type="submit" class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors {{ $faq->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <span class="inline-block w-4 h-4 transform bg-white rounded-full shadow transition-transform {{ $faq->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="text-[#0F767E] hover:bg-teal-50 p-2 rounded-lg transition-colors" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" onsubmit="return confirm('Hapus FAQ ini?')">
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
                            <i class="fa-solid fa-circle-question text-3xl block mb-2 text-gray-300"></i>
                            Belum ada FAQ. <a href="{{ route('admin.faqs.create') }}" class="text-[#0F767E] font-bold">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $faqs->links() }}
    </div>
</div>
@endsection
