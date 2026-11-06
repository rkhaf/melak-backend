<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\UserBanned;
use App\Support\Audit;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->withCount('fishScans')
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%")
                        ->orWhere('email', 'like', "%{$request->q}%")
                        ->orWhere('username', 'like', "%{$request->q}%");
                });
            })
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->where('role', $request->role);
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'phone' => 'nullable|string|max:255',
            'role' => 'required|in:superadmin,admin,user',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'role.in' => 'Role tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        Audit::log('user.created', 'Akun "'.$data['name'].'" ('.$data['role'].') berhasil dibuat.', $user);

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Akun "'.$data['name'].'" berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $user->loadCount('fishScans');
        $user->load('notificationSettings');

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Proteksi: admin tidak boleh menurunkan akun sendiri
        if ($user->id === auth()->id() && $request->input('role') !== $user->role) {
            return back()->withErrors([
                'role' => 'Anda tidak dapat mengubah role akun sendiri.',
            ]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => 'nullable|string|max:255',
            'role' => 'required|in:superadmin,admin,user',
            'is_active' => 'sometimes|boolean',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'role.in' => 'Role tidak valid.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        Audit::log('user.updated', 'Data akun "'.$data['name'].'" diperbarui.', $user);

        return redirect()->route('admin.users.index')
            ->with('success', 'Data pengguna "'.$data['name'].'" berhasil diperbarui.');
    }

    public function updateNotifications(Request $request, User $user)
    {
        $validated = $request->validate([
            'via_push' => 'sometimes|array',
            'via_push.*' => 'boolean',
            'via_email' => 'sometimes|array',
            'via_email.*' => 'boolean',
            'via_inapp' => 'sometimes|array',
            'via_inapp.*' => 'boolean',
        ]);

        foreach (Notification::TYPES as $type) {
            NotificationSetting::updateOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                [
                    'via_push' => $request->boolean("via_push.$type"),
                    'via_email' => $request->boolean("via_email.$type"),
                    'via_inapp' => $request->boolean("via_inapp.$type"),
                ]
            );
        }

        unset($validated);

        Audit::log('user.notification_settings', 'Preferensi notifikasi akun "'.$user->name.'" diperbarui.', $user);

        return redirect()->back()
            ->with('success', 'Preferensi notifikasi untuk "'.$user->name.'" berhasil disimpan.');
    }

    public function toggle(User $user)
    {
        // Proteksi: admin tidak boleh menonaktifkan akun sendiri
        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'toggle' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        Audit::log('user.toggled', 'Akun "'.$user->name.'" '.($user->is_active ? 'diaktifkan' : 'dinonaktifkan').'.', $user);

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun "'.$user->name.'" '.($user->is_active ? 'diaktifkan' : 'dinonaktifkan').'.');
    }

    public function resetPassword(User $user)
    {
        $password = $this->generatePassword();
        $user->password = Hash::make($password);
        $user->save();

        Audit::log('user.password_reset', 'Kata sandi akun "'.$user->name.'" direset oleh admin.', $user);

        return redirect()->route('admin.users.edit', $user)
            ->with('info', 'Kata sandi baru untuk "'.$user->name.'" adalah: <strong>'.$password.'</strong> (catat dan simpan, hanya ditampilkan sekali).');
    }

    /**
     * FR-09.3.2 — suspend/ban akun dengan alasan & durasi.
     */
    public function ban(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['ban' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'duration_days' => 'nullable|integer|min:1|max:365',
        ], [
            'reason.required' => 'Alasan penonaktifan wajib diisi.',
            'duration_days.integer' => 'Durasi suspend harus berupa angka hari.',
        ]);

        $until = isset($data['duration_days']) ? now()->addDays((int) $data['duration_days']) : null;

        $user->update([
            'is_active' => false,
            'banned_at' => now(),
            'ban_reason' => $data['reason'],
            'ban_expires_at' => $until,
        ]);

        Audit::log(
            'user.banned',
            'Akun "'.$user->name.'" dinonaktifkan. Alasan: '.$data['reason'].'. Durasi: '.($until ? $until->format('d/m/Y') : 'permanen').'.',
            $user
        );

        Notifier::send(
            $user,
            'security',
            'Akun dinonaktifkan',
            'Akun Anda dinonaktifkan oleh administrator. Alasan: '.$data['reason'],
            ['reason' => $data['reason'], 'until' => $until?->toIso8601String()],
            fn () => $user->notify(new UserBanned($data['reason'], $until?->format('d/m/Y H:i')))
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun "'.$user->name.'" berhasil dinonaktifkan.');
    }

    /**
     * FR-09.3.2 — cabut suspend/ban.
     */
    public function unban(User $user)
    {
        $user->update([
            'is_active' => true,
            'banned_at' => null,
            'ban_reason' => null,
            'ban_expires_at' => null,
        ]);

        Audit::log('user.unbanned', 'Akun "'.$user->name.'" diaktifkan kembali.', $user);

        Notifier::send(
            $user,
            'security',
            'Akun diaktifkan kembali',
            'Akun Anda telah diaktifkan kembali oleh administrator.',
            [],
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'Akun "'.$user->name.'" berhasil diaktifkan kembali.');
    }

    private function generatePassword(): string
    {
        return 'Kwd'.strtoupper(\Str::random(6)).'@'.random_int(10, 99);
    }
}
