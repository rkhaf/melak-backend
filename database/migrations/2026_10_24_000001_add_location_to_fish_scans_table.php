<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koordinat GPS saat scan dilakukan (dikirim aplikasi, FR-07.3).
     *
     * Dipakai MapController untuk menempatkan titik peta sebaran dari
     * posisi scan langsung, bukan hanya dari lokasi kolam.
     */
    public function up(): void
    {
        Schema::table('fish_scans', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 7)->nullable()->after('symptoms_desc');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
        });
    }

    public function down(): void
    {
        Schema::table('fish_scans', function (Blueprint $table) {
            $table->dropColumn(['location_lat', 'location_lng']);
        });
    }
};
