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
        Schema::create('internal_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->morphs('commentable', 'idx_internal_comments_commentable');
            $table->text('comment');
            $table->string('type', 30)->default('internal'); // internal, agent_visible, system
            $table->json('mentioned_user_ids')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_internal_comments_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_comments');
    }
};
