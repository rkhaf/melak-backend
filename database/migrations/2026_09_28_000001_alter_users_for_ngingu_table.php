<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bio')->nullable()->after('avatar_url');
            $table->string('province')->nullable()->after('bio');
            $table->unsignedInteger('reputation_points')->default(0)->after('province');
            $table->string('reputation_level')->default('Pemula')->after('reputation_points');
            $table->string('username')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'province', 'reputation_points', 'reputation_level']);
        });
    }
};
