<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave: types, per-year balances, and the requests themselves.
 *
 * The balance is stored rather than derived. Deriving it from approved
 * requests looks tidier but falls apart the moment entitlement changes
 * mid-year, someone carries days forward, or HR grants a one-off adjustment —
 * all of which are ordinary. A stored balance also gives payroll a figure it
 * can rely on without replaying the whole request history.
 *
 * `pending_days` is tracked separately from `taken_days` so an employee cannot
 * spend the same day twice by lodging two requests before either is approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();

            $table->decimal('days_per_year', 5, 2)->default(0);
            $table->boolean('is_paid')->default(true);

            // Sick leave beyond a few days normally needs a medical note.
            $table->boolean('requires_document')->default(false);
            $table->unsignedSmallInteger('document_required_after_days')->default(0);

            $table->decimal('max_carry_forward_days', 5, 2)->default(0);

            // Some types apply to one gender only (maternity, paternity).
            $table->enum('applies_to', ['all', 'male', 'female'])->default('all');

            // Days of service before an employee may use this type.
            $table->unsignedSmallInteger('minimum_service_days')->default(0);

            $table->string('colour', 20)->default('secondary');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->year('year');

            $table->decimal('entitled_days', 6, 2)->default(0);
            $table->decimal('carried_forward_days', 6, 2)->default(0);
            $table->decimal('adjustment_days', 6, 2)->default(0);
            $table->decimal('taken_days', 6, 2)->default(0);
            $table->decimal('pending_days', 6, 2)->default(0);

            $table->string('adjustment_reason')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
            $table->index(['year', 'leave_type_id']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date');

            /*
             * Working days, computed when the request is lodged and then left
             * alone. Recomputing later would silently rewrite history if the
             * working-week rules ever change.
             */
            $table->decimal('days', 5, 2);
            $table->boolean('is_half_day')->default(false);

            $table->text('reason')->nullable();
            $table->string('contact_during_leave', 120)->nullable();
            $table->string('document_path')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Who covers the work while they are away.
            $table->foreignId('covering_employee_id')->nullable()
                ->constrained('employees')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
