<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->foreignId('disease_id')->nullable()->constrained('diseases')->nullOnDelete();
            $table->string('label');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('disease_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_datasets');
    }
};
