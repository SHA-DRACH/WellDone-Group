<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Internal announcements — visible only to signed-in staff, never on the
 * public website.
 *
 * An announcement may be targeted at everyone, one department, or one
 * division; leaving both null means company-wide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->string('excerpt', 500)->nullable();

            $table->enum('category', [
                'general', 'holiday', 'meeting', 'promotion', 'birthday',
                'event', 'policy', 'training', 'urgent',
            ])->default('general');

            // Targeting. Both null = the whole company.
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();

            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attachment')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('notify_by_email')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'published_at']);
            $table->index(['department_id', 'is_active']);
            $table->index('is_pinned');
        });

        // Read receipts, so HR can see whether a policy update was actually seen.
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
