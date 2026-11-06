<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbreak_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_id')->constrained('diseases')->cascadeOnDelete();
            $table->string('province');
            $table->unsignedInteger('cases_7d')->default(0);
            $table->unsignedInteger('threshold')->default(5);
            $table->text('message');
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamps();

            $table->unique(['disease_id', 'province']);
            $table->index('status');
        });

        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('filename');
            $table->string('s3_path')->nullable();
            $table->json('disease_labels')->nullable();
            $table->decimal('accuracy_pct', 5, 2)->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_models');
        Schema::dropIfExists('outbreak_alerts');
    }
};
