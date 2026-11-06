<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->enum('category', ['bacteria', 'virus', 'parasite', 'fungi', 'other'])->default('other');
            $table->enum('severity', ['ringan', 'sedang', 'berat', 'kritis'])->default('sedang');
            $table->text('description')->nullable();
            $table->text('causes')->nullable();
            $table->text('symptoms')->nullable();
            $table->unsignedSmallInteger('recovery_days_min')->nullable();
            $table->unsignedSmallInteger('recovery_days_max')->nullable();
            $table->boolean('is_published')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diseases');
    }
};
