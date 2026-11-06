@extends('layouts.admin')

@section('title', 'Profil Saya')
@section('header_title', 'Profil Saya')

@section('content')
@php $user = auth()->user(); @endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Edit profil -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8">
        <h3 class="text-lg font-extrabold text-[#1F2937] mb-6"><i class="fa-solid fa-user text-[#0F767E] mr-2"></i> Data Profil</h3>

        <form action="{{ route('admin.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                    @error('username') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Nomor Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Profil
                </button>
            </div>
        </form>
    </div>

    <!-- Side panel -->
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
            @if($user->avatar_url)
                <img src="{{ \Illuminate\Support\Str::startsWith($user->avatar_url, 'http') ? $user->avatar_url : asset('storage/' . str_replace('storage/', '', $user->avatar_url)) }}" class="w-20 h-20 rounded-full object-cover border border-gray-200 mx-auto mb-3" alt="">
            @else
                <div class="w-20 h-20 rounded-full bg-[#0F767E] flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            @endif
            <h4 class="font-extrabold text-[#1F2937]">{{ $user->name }}</h4>
            <p class="text-xs text-gray-400">{{ $user->email }}</p>
            <span class="inline-block mt-3 text-[11px] text-[#0F767E] bg-teal-50 border border-teal-200 px-2.5 py-1 rounded font-bold uppercase">{{ $user->role }}</span>
        </div>

        <!-- Ganti password -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-extrabold text-[#1F2937] mb-2"><i class="fa-solid fa-key text-[#F97316] mr-2"></i> Ganti Kata Sandi</h4>
            <p class="text-xs text-gray-400 mb-4">Wajib memasukkan kata sandi saat ini untuk mengganti.</p>
            <form action="{{ route('admin.profile.password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Kata Sandi Saat Ini</label>
                    <input type="password" name="current_password" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                    @error('current_password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Kata Sandi Baru</label>
                    <input type="password" name="password" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                    @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" name="password_confirmation" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40">
                </div>
                <button type="submit" class="w-full bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    <i class="fa-solid fa-rotate mr-1.5"></i> Ganti Kata Sandi
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
