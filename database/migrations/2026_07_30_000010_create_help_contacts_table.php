<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // phone, email, website
            $table->string('title');
            $table->string('value');
            $table->string('icon')->nullable(); // icon name hint for frontend
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_contacts');
    }
};
