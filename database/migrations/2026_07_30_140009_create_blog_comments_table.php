<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional article comments. Everything is held for moderation by default —
 * nothing reaches the public page until an editor approves it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_email');
            $table->string('author_website')->nullable();
            $table->text('body');
            $table->boolean('is_approved')->default(false);
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['blog_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
