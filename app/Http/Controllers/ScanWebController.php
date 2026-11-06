<?php

namespace App\Http\Controllers;

use App\Models\FishScan;
use Illuminate\Contracts\View\View;

/**
 * Halaman web hasil scan — target link tombol "Lihat Hasil" di email
 * notifikasi (FR-06.1).
 *
 * Route dilindungi `signed`: pengguna aplikasi tidak punya sesi web
 * (login hanya lewat API), jadi tanda tangan URL yang menjamin link hanya
 * sah bila dibuat oleh backend — bukan lewat login.
 */
class ScanWebController extends Controller
{
    public function show(string $id): View
    {
        $scan = FishScan::with([
            'pond',
            'images',
            'result.disease.treatments',
            'result.disease.preventions',
        ])->find($id);

        abort_unless($scan, 404);

        return view('scans.show', [
            'scan' => $scan,
            'result' => $scan->result,
            'disease' => $scan->result?->disease,
        ]);
    }
}
