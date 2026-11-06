<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\Audit;
use Illuminate\Http\Request;

/**
 * FR-09 / §11 — pengaturan aplikasi (app_settings key-value).
 */
class SettingAdminController extends Controller
{
    public function index()
    {
        $settings = AppSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $values = $request->input('values', []);
        $updated = 0;

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $setting = AppSetting::where('key', $key)->first();

                if ($setting === null) {
                    continue;
                }

                $setting->update(['value' => $value]);
                $updated++;
            }
        }

        Audit::log('settings.updated', $updated.' pengaturan aplikasi diperbarui.');

        return redirect()->route('admin.settings.index')
            ->with('success', $updated.' pengaturan berhasil disimpan.');
    }
}
