<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppInfo;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppInfoController extends Controller
{
    public function edit()
    {
        $appInfo = AppInfo::first();

        if (! $appInfo) {
            $appInfo = AppInfo::create([
                'app_name' => 'Melak',
                'developer' => 'Tim Melak',
            ]);
        }

        return view('admin.app-info.edit', compact('appInfo'));
    }

    public function update(Request $request)
    {
        $appInfo = AppInfo::firstOrFail();

        $data = $request->validate([
            'app_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'developer' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'support_email' => 'nullable|email|max:255',
            'privacy_policy_url' => 'nullable|url|max:255',
            'terms_url' => 'nullable|url|max:255',
        ], [
            'app_name.required' => 'Nama aplikasi wajib diisi.',
            'developer.required' => 'Nama developer wajib diisi.',
            'website.url' => 'Format website tidak valid.',
            'support_email.email' => 'Format email tidak valid.',
            'privacy_policy_url.url' => 'Format URL tidak valid.',
            'terms_url.url' => 'Format URL tidak valid.',
        ]);

        $appInfo->update($data);

        return redirect()->route('admin.app-info.edit')
            ->with('success', 'Konfigurasi aplikasi berhasil diperbarui.');
    }

    public function uploadApk(Request $request)
    {
        $data = $request->validate([
            'apk_link' => 'required|url',
            'apk_version' => 'nullable|string|max:50',
        ], [
            'apk_link.required' => 'Link APK wajib diisi.',
            'apk_link.url' => 'Format link tidak valid.',
        ]);

        $appInfo = AppInfo::firstOrFail();

        // Hapus file lama jika ada (untuk membersihkan file sebelumnya jika ada)
        if ($appInfo->apk_path && ! filter_var($appInfo->apk_path, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($appInfo->apk_path)) {
            Storage::disk('public')->delete($appInfo->apk_path);
        }

        $appInfo->update([
            'apk_path' => $data['apk_link'],
            'apk_filename' => 'External Link',
            'apk_version' => $data['apk_version'] ?? null,
            'apk_uploaded_at' => now(),
        ]);

        Audit::log('app.apk_uploaded', 'Link APK aplikasi diperbarui (versi '.($data['apk_version'] ?? '-').').', $appInfo);

        return redirect()->route('admin.app-info.edit')
            ->with('success', 'APK berhasil diunggah. Tombol unduh di landing page kini aktif.');
    }

    public function removeApk()
    {
        $appInfo = AppInfo::firstOrFail();

        if ($appInfo->apk_path && ! filter_var($appInfo->apk_path, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($appInfo->apk_path)) {
            Storage::disk('public')->delete($appInfo->apk_path);
        }

        $appInfo->update([
            'apk_path' => null,
            'apk_filename' => null,
            'apk_version' => null,
            'apk_uploaded_at' => null,
        ]);

        Audit::log('app.apk_removed', 'File APK aplikasi dihapus dari sistem.', $appInfo);

        return redirect()->route('admin.app-info.edit')
            ->with('success', 'APK berhasil dihapus. Tombol unduh akan disembunyikan.');
    }
}
