<?php

declare(strict_types=1);

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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_category_id')->constrained('article_categories')->onDelete('restrict');
            $table->foreignId('author_id')->constrained('users')->onDelete('restrict');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image_path', 255)->nullable();
            $table->integer('reading_time_minutes')->default(1);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_description', 255)->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at'], 'idx_articles_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
