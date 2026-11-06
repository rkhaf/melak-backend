<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_id')->constrained('diseases')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('medicines')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->timestamps();
        });

        Schema::create('disease_preventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_id')->constrained('diseases')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order')->default(1);
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_preventions');
        Schema::dropIfExists('disease_treatments');
    }
};
