<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppInfo;

class AppInfoController extends Controller
{
    public function index()
    {
        $appInfo = AppInfo::first();

        if (! $appInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Info aplikasi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $appInfo,
        ]);
    }
}
