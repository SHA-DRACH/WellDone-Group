<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two ways documents move between the divisions and the executive:
 *
 * - document_reviews: a division submits a document (report, contract,
 *   proposal…) for the CEO or Managing Director to review, who approves it,
 *   rejects it, or asks for changes.
 * - staff_messages: the executive sends a message, optionally with a
 *   document, to one member of staff, who can reply.
 *
 * Files are kept on the private disk and only ever served through an
 * authorised controller action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category', 40)->default('report');
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['division_id', 'status']);
        });

        Schema::create('staff_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('staff_messages')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'read_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_messages');
        Schema::dropIfExists('document_reviews');
    }
};
