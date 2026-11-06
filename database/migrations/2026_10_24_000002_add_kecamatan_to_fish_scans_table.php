<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama kecamatan hasil pemetaan koordinat scan → polygon wilayah
     * (KecamatanResolver, FR-07.3 peta sebaran per kecamatan).
     *
     * Null berarti titik di luar polygon Indramayu / belum dihitung —
     * gunakan `php artisan scan:backfill-kecamatan` untuk data lama.
     */
    public function up(): void
    {
        Schema::table('fish_scans', function (Blueprint $table) {
            $table->string('kecamatan', 80)->nullable()->after('location_lng')->index();
        });
    }

    public function down(): void
    {
        Schema::table('fish_scans', function (Blueprint $table) {
            $table->dropColumn('kecamatan');
        });
    }
};
