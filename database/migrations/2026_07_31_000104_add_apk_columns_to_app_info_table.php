<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_info', function (Blueprint $table) {
            $table->string('apk_path')->nullable()->after('terms_content');
            $table->string('apk_filename')->nullable()->after('apk_path');
            $table->string('apk_version')->nullable()->after('apk_filename');
            $table->timestamp('apk_uploaded_at')->nullable()->after('apk_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_info', function (Blueprint $table) {
            $table->dropColumn(['apk_path', 'apk_filename', 'apk_version', 'apk_uploaded_at']);
        });
    }
};
