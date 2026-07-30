<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound quotation requests — the primary lead-generation record. The
 * `reference` is what the requester sees in their confirmation email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('contact_name');
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('project_title');
            $table->longText('description');
            $table->string('budget_range')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('timeline')->nullable();
            $table->date('preferred_start_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->enum('status', ['new', 'in_review', 'quoted', 'won', 'lost', 'closed'])->default('new');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->decimal('quoted_amount', 15, 2)->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('source')->default('website');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['division_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('created_at');
        });

        Schema::create('quote_request_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            $table->unique(['quote_request_id', 'service_id'], 'quote_service_unique');
        });

        Schema::create('quote_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained('quote_requests')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index('quote_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_request_files');
        Schema::dropIfExists('quote_request_service');
        Schema::dropIfExists('quote_requests');
    }
};
