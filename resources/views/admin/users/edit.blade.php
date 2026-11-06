@extends('layouts.admin')

@section('title', 'Edit Pengguna')
@section('header_title', 'Edit Pengguna')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-400 hover:text-gray-600"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke daftar</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Edit form -->
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8">
        <h3 class="text-lg font-extrabold text-[#1F2937] mb-6"><i class="fa-solid fa-pen text-[#0F767E] mr-2"></i> Edit Pengguna</h3>

        @if ($errors->has('toggle'))
            <div class="mb-5 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg text-rose-800 text-sm">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $errors->first('toggle') }}
            </div>
        @endif

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
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
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Role</label>
                    <select name="role" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <option value="superadmin" @selected(old('role', $user->role) === 'superadmin')>Super Admin</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                        <option value="user" @selected(old('role', $user->role) === 'user')>Warga</option>
                    </select>
                    @if($user->id === auth()->id())
                        <p class="text-xs text-gray-400 mt-1">Role akun sendiri tidak dapat diubah.</p>
                    @endif
                    @error('role') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-600 mb-1.5">Status Akun</label>
                    <select name="is_active" class="w-full text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F767E]/40" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        <option value="1" @selected((bool) old('is_active', $user->is_active))>Aktif</option>
                        <option value="0" @selected(!(bool) old('is_active', $user->is_active))>Nonaktif</option>
                    </select>
                    @if($user->id === auth()->id())
                        <p class="text-xs text-gray-400 mt-1">Status akun sendiri tidak dapat diubah.</p>
                    @endif
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <button type="submit" class="bg-[#0F767E] hover:bg-[#0a5258] text-white text-sm font-bold px-6 py-2.5 rounded-xl shadow-sm transition-colors">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Perubahan
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-bold px-6 py-2.5 rounded-xl transition-colors">Batal</a>
            </div>
        </form>
    </div>

    <!-- Side panel -->
    <div class="space-y-6">
        <!-- Profile summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 text-center">
            @if($user->avatar_url)
                <img src="{{ \Illuminate\Support\Str::startsWith($user->avatar_url, 'http') ? $user->avatar_url : asset('storage/' . str_replace('storage/', '', $user->avatar_url)) }}" class="w-20 h-20 rounded-full object-cover border border-gray-200 mx-auto mb-3" alt="">
            @else
                <div class="w-20 h-20 rounded-full bg-[#0F767E] flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            @endif
            <h4 class="font-extrabold text-[#1F2937]">{{ $user->name }}</h4>
            <p class="text-xs text-gray-400">{{ $user->email }}</p>
            <div class="grid grid-cols-2 gap-3 mt-5 pt-5 border-t border-gray-100">
                <div>
                    <p class="text-2xl font-black text-[#0F767E]">{{ $user->fish_scans_count }}</p>
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Scan</p>
                </div>
                <div>
                    <p class="text-2xl font-black text-[#0F767E]">{{ $user->created_at?->format('d/m/Y') }}</p>
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Terdaftar</p>
                </div>
            </div>
        </div>

        <!-- Reset password -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-extrabold text-[#1F2937] mb-2"><i class="fa-solid fa-key text-[#F97316] mr-2"></i> Reset Kata Sandi</h4>
            <p class="text-xs text-gray-400 mb-4">Generate kata sandi baru untuk akun ini. Kata sandi lama akan langsung diganti.</p>
            <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" onsubmit="return confirm('Yakin ingin mereset kata sandi akun ini?')">
                @csrf
                <button type="submit" class="w-full bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    <i class="fa-solid fa-rotate mr-1.5"></i> Reset Kata Sandi
                </button>
            </form>
        </div>

        <!-- Notification settings -->
        @php
            $nsRows = $user->notificationSettings->keyBy('type');
            $typeLabels = [
                'scan_result' => 'Hasil Scan',
                'forum_reply' => 'Balasan Forum',
                'mention' => 'Sebutan (@mention)',
                'outbreak' => 'Wabah Penyakit',
                'tips' => 'Tips Harian',
                'security' => 'Keamanan Akun',
                'weekly' => 'Ringkasan Mingguan',
            ];
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-extrabold text-[#1F2937] mb-1"><i class="fa-solid fa-bell text-[#0F767E] mr-2"></i> Preferensi Notifikasi</h4>
            <p class="text-xs text-gray-400 mb-4">Kanal notifikasi per jenis notifikasi untuk pengguna ini.</p>
            <form action="{{ route('admin.users.notifications', $user) }}" method="POST">
                @csrf
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] uppercase text-gray-400 border-b border-gray-100">
                            <th class="text-left font-bold pb-2">Jenis</th>
                            <th class="font-bold pb-2">Push</th>
                            <th class="font-bold pb-2">Email</th>
                            <th class="font-bold pb-2">In-App</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($typeLabels as $type => $label)
                            @php $row = $nsRows->get($type); @endphp
                            <tr class="border-b border-gray-50">
                                <td class="py-2 text-gray-600">{{ $label }}</td>
                                <td class="text-center"><input type="checkbox" name="via_push[{{ $type }}]" value="1" {{ (!$row || $row->via_push) ? 'checked' : '' }} class="accent-[#0F767E] w-4 h-4"></td>
                                <td class="text-center"><input type="checkbox" name="via_email[{{ $type }}]" value="1" {{ (!$row || $row->via_email) ? 'checked' : '' }} class="accent-[#0F767E] w-4 h-4"></td>
                                <td class="text-center"><input type="checkbox" name="via_inapp[{{ $type }}]" value="1" {{ (!$row || $row->via_inapp) ? 'checked' : '' }} class="accent-[#0F767E] w-4 h-4"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="submit" class="w-full mt-4 bg-[#0F767E]/10 hover:bg-[#0F767E]/20 text-[#0F767E] text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Preferensi
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
