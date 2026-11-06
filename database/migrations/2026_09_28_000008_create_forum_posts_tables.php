<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('post_count')->default(0);
            $table->timestamps();
        });

        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('forum_categories')->cascadeOnDelete();
            $table->foreignUuid('scan_id')->nullable()->constrained('fish_scans')->nullOnDelete();
            $table->string('title');
            $table->text('content_html');
            $table->json('image_urls')->nullable();
            $table->enum('type', ['question', 'discussion'])->default('question');
            $table->enum('status', ['pending', 'published', 'rejected', 'locked'])->default('published');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->integer('votes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->boolean('has_best_answer')->default(false);
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('forum_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });

        Schema::create('forum_post_tags', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('forum_posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('forum_tags')->cascadeOnDelete();

            $table->primary(['post_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_tags');
        Schema::dropIfExists('forum_tags');
        Schema::dropIfExists('forum_posts');
        Schema::dropIfExists('forum_categories');
    }
};
