@extends('layouts.admin')

@section('title', 'Kontak Darurat')
@section('header_title', 'Kontak Darurat')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Daftar Kontak Darurat</h3>
        <p class="text-sm text-gray-400">Kontak bantuan yang ditampilkan di halaman Bantuan aplikasi</p>
    </div>
    <a href="{{ route('admin.contacts.create') }}" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-colors flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Tambah Kontak
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <th class="px-5 py-3.5 w-16">#</th>
                    <th class="px-5 py-3.5">Judul</th>
                    <th class="px-5 py-3.5">Tipe</th>
                    <th class="px-5 py-3.5">Nilai</th>
                    <th class="px-5 py-3.5">Urutan</th>
                    <th class="px-5 py-3.5">Status</th>
                    <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="contact-sortable">
                @forelse($contacts as $index => $contact)
                    <tr data-id="{{ $contact->id }}" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4 text-gray-400"><i class="fa-solid fa-grip-vertical cursor-grab"></i></td>
                        <td class="px-5 py-4 font-bold text-[#1F2937]">
                            <span class="inline-flex items-center gap-2">
                                @if($contact->icon)
                                    @php
                                        $prefix = ($contact->icon === 'whatsapp') ? 'fa-brands' : 'fa-solid';
                                    @endphp
                                    <i class="{{ $prefix }} fa-{{ $contact->icon }} text-[#0F767E]"></i>
                                @endif
                                {{ $contact->title }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-[11px] bg-gray-100 text-gray-600 px-2.5 py-1 rounded font-bold uppercase">{{ \App\Support\UiHelper::contactType($contact->type) }}</span>
                        </td>
                        <td class="px-5 py-4 text-gray-500">{{ $contact->value }}</td>
                        <td class="px-5 py-4 text-gray-400 text-xs">{{ $contact->sort_order }}</td>
                        <td class="px-5 py-4">
                            <form action="{{ route('admin.contacts.toggle', $contact) }}" method="POST">
                                @csrf
                                <button type="submit" class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors {{ $contact->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <span class="inline-block w-4 h-4 transform bg-white rounded-full shadow transition-transform {{ $contact->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.contacts.edit', $contact) }}" class="text-[#0F767E] hover:bg-teal-50 p-2 rounded-lg transition-colors" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" onsubmit="return confirm('Hapus kontak ini?')">
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
                        <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-phone-flip text-3xl block mb-2 text-gray-300"></i>
                            Belum ada kontak darurat. <a href="{{ route('admin.contacts.create') }}" class="text-[#0F767E] font-bold">Tambah sekarang</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('contact-sortable');
        if (!tbody) return;

        new Sortable(tbody, {
            handle: '.fa-grip-vertical',
            animation: 150,
            onEnd: function () {
                const order = [];
                tbody.querySelectorAll('tr[data-id]').forEach(function (tr) {
                    order.push(tr.getAttribute('data-id'));
                });

                fetch('{{ route('admin.contacts.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ order: order })
                }).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    if (data.success) location.reload();
                });
            }
        });
    });
</script>
@endsection
