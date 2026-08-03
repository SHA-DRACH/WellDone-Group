<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organisational structure for the HR module.
 *
 * `divisions` (already present) are the outward-facing business units shown
 * on the website. `departments` are the internal org units staff belong to —
 * which includes the four business divisions plus the central functions
 * (Finance, Administration, Marketing, Human Resources, Executive Office).
 *
 * A department may map to a division where the two correspond, so ICT
 * Services exists once as a public division and once as the department its
 * people report into, without duplicating the name in two unrelated lists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->text('description')->nullable();

            // Optional link to the public-facing business division.
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();

            // Set after employees exist; nullable to avoid a circular dependency.
            $table->unsignedBigInteger('head_employee_id')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();

            /*
             * Seniority band, used for reporting and for ordering an org
             * chart. Lower numbers are more senior, so an ascending sort puts
             * the executive first.
             */
            $table->unsignedTinyInteger('grade')->default(5);

            $table->decimal('min_salary', 15, 2)->nullable();
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->unsignedInteger('headcount_budget')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'is_active']);
            $table->index('grade');
        });

        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_permanent')->default(false);
            $table->boolean('accrues_leave')->default(true);
            $table->unsignedInteger('probation_months')->default(3);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
