<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => 'nullable|string|max:255',
        ]);

        $user->update($data);

        Audit::log('profile.updated', 'Data profil sendiri diperbarui.', $user);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('Kata sandi saat ini salah.');
                }
            }],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($data['password']);
        $user->save();

        Audit::log('profile.password_changed', 'Kata sandi akun sendiri diganti.', $user);

        return back()->with('success', 'Kata sandi berhasil diganti.');
    }
}
