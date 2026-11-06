<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('pond_id')->nullable()->constrained('user_ponds')->nullOnDelete();
            $table->string('fish_type', 64)->nullable();
            $table->text('symptoms_desc')->nullable();
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('processing');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('scan_images', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('scan_id')->constrained('fish_scans')->cascadeOnDelete();
            $table->string('s3_key');
            $table->string('s3_url');
            $table->boolean('is_primary')->default(false);
            $table->string('gradcam_url')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('scan_id')->constrained('fish_scans')->cascadeOnDelete();
            $table->foreignId('disease_id')->nullable()->constrained('diseases')->nullOnDelete();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('top_predictions')->nullable();
            $table->boolean('low_confidence_warning')->default(false);
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();

            $table->unique('scan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_results');
        Schema::dropIfExists('scan_images');
        Schema::dropIfExists('fish_scans');
    }
};
