@extends('layouts.admin')

@section('title', 'Pengaturan Aplikasi')
@section('header_title', 'Pengaturan Aplikasi')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Pengaturan Aplikasi</h3>
    <p class="text-sm text-gray-400">Kelola nilai konfigurasi aplikasi (app_settings) per kelompok (FR-09)</p>
</div>

<form action="{{ route('admin.settings.update') }}" method="POST">
    @csrf

    @forelse($settings as $group => $items)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
            <h4 class="font-bold text-sm text-[#0F767E] uppercase tracking-wider mb-4">
                <i class="fa-solid fa-folder-tree mr-1"></i> {{ $group }}
            </h4>

            <div class="space-y-4">
                @foreach ($items as $setting)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                        <div class="md:col-span-1">
                            <div class="font-mono text-xs font-bold text-gray-600">{{ $setting->key }}</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">
                                {{ $setting->is_public ? 'publik' : 'internal' }}
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <textarea name="values[{{ $setting->key }}]" rows="2"
                                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500/40">{{ old('values.'.$setting->key, $setting->value) }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center text-gray-400">
            <i class="fa-solid fa-gears text-3xl block mb-2 text-gray-300"></i>
            Belum ada pengaturan tersimpan.
        </div>
    @endforelse

    @if (count($settings) > 0)
        <div class="flex items-center gap-3">
            <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Semua Pengaturan
            </button>
        </div>
    @endif
</form>
@endsection
