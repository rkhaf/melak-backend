<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_images', function (Blueprint $table) {
            // Key file di disk upload — dipakai untuk menghapus file saat gambar
            // dihapus dari panel admin (URL saja tidak bisa dipakai untuk delete).
            $table->string('s3_key')->nullable()->after('s3_url');
        });
    }

    public function down(): void
    {
        Schema::table('disease_images', function (Blueprint $table) {
            $table->dropColumn('s3_key');
        });
    }
};
