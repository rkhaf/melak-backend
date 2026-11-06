<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_info', function (Blueprint $table) {
            $table->text('privacy_policy_content')->nullable()->after('privacy_policy_url');
            $table->text('terms_content')->nullable()->after('terms_url');
        });
    }

    public function down(): void
    {
        Schema::table('app_info', function (Blueprint $table) {
            $table->dropColumn(['privacy_policy_content', 'terms_content']);
        });
    }
};
