@extends('layouts.admin')

@section('title', 'Konfigurasi Aplikasi')
@section('header_title', 'Konfigurasi Aplikasi')

@section('content')
<div class="mb-6">
    <div>
        <h3 class="text-lg font-extrabold text-[#1F2937]">Pengaturan Informasi Aplikasi</h3>
        <p class="text-sm text-gray-400">Data ini tampil di halaman Tentang / Profil aplikasi Melak</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 max-w-4xl">
    <form action="{{ route('admin.app-info.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Nama Aplikasi</label>
                <input type="text" name="app_name" value="{{ old('app_name', $appInfo->app_name) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('app_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Developer</label>
                <input type="text" name="developer" value="{{ old('developer', $appInfo->developer) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('developer') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Website</label>
                <input type="url" name="website" value="{{ old('website', $appInfo->website) }}" placeholder="https://..." class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('website') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Email Dukungan</label>
                <input type="email" name="support_email" value="{{ old('support_email', $appInfo->support_email) }}" placeholder="email@domain.com" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('support_email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">URL Kebijakan Privasi</label>
                <input type="url" name="privacy_policy_url" value="{{ old('privacy_policy_url', $appInfo->privacy_policy_url) }}" placeholder="https://..." class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('privacy_policy_url') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">URL Syarat & Ketentuan</label>
                <input type="url" name="terms_url" value="{{ old('terms_url', $appInfo->terms_url) }}" placeholder="https://..." class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('terms_url') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-bold text-gray-600 mb-1.5">Deskripsi Aplikasi</label>
            <textarea name="description" rows="4" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" placeholder="Deskripsi singkat aplikasi">{{ old('description', $appInfo->description) }}</textarea>
            @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-6">
            <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Konfigurasi
            </button>
            <button type="reset" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-bold px-6 py-2.5 rounded-xl transition-colors">Reset Form</button>
        </div>
    </form>
</div>

<!-- APK Upload -->
<div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8 max-w-4xl">
    <h3 class="text-lg font-extrabold text-[#1F2937] mb-1"><i class="fa-solid fa-file-arrow-up text-[#0F767E] mr-2"></i> File Aplikasi (APK)</h3>
    <p class="text-sm text-gray-400 mb-5">Unggah file APK terbaru untuk dibagikan ke pengguna aplikasi mobile.</p>

    @if($appInfo->apk_path)
        <div class="flex items-center gap-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl mb-5">
            <div class="w-11 h-11 rounded-xl bg-emerald-500/15 flex items-center justify-center text-emerald-600 shrink-0">
                <i class="fa-solid fa-file-zipper text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-[#1F2937] text-sm truncate">{{ $appInfo->apk_filename }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if($appInfo->apk_version)Versi {{ $appInfo->apk_version }} · @endif
                    Diunggah {{ $appInfo->apk_uploaded_at?->format('d M Y H:i') }}
                </p>
                <a href="{{ filter_var($appInfo->apk_path, FILTER_VALIDATE_URL) ? $appInfo->apk_path : \Illuminate\Support\Facades\Storage::disk('public')->url($appInfo->apk_path) }}" target="_blank" class="text-xs font-bold text-[#0F767E] hover:underline inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-down-left-and-up-right-to-center"></i> Pratinjau unduhan
                </a>
            </div>
            <form action="{{ route('admin.app-info.apk.remove') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus file APK ini?')">
                @csrf
                <button type="submit" class="bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    <i class="fa-solid fa-trash mr-1.5"></i> Hapus
                </button>
            </form>
        </div>
    @else
        <div class="p-4 bg-gray-50 border border-dashed border-gray-300 rounded-xl text-center text-sm text-gray-400 mb-5">
            <i class="fa-solid fa-cloud-arrow-up text-2xl block mb-2 text-gray-300"></i>
            Belum ada file APK. Unggah di bawah untuk mengaktifkan tombol unduh.
        </div>
    @endif

    <form action="{{ route('admin.app-info.apk.upload') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Link APK (Google Drive/Lainnya)</label>
                <input type="url" name="apk_link" placeholder="https://..." class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40 bg-white">
                @error('apk_link') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-600 mb-1.5">Versi (opsional)</label>
                <input type="text" name="apk_version" value="{{ old('apk_version', $appInfo->apk_version) }}" placeholder="Contoh: 1.2.0" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                @error('apk_version') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex gap-3 pt-6">
            <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Unggah APK
            </button>
        </div>
    </form>
</div>
@endsection
