<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidate submissions against a published vacancy. Résumés are stored on
 * the private disk; only the relative path is kept here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->nullable()->constrained('careers')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('resume_path');
            $table->string('resume_original_name')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->string('highest_qualification')->nullable();
            $table->string('expected_salary')->nullable();
            $table->date('available_from')->nullable();
            $table->enum('status', ['new', 'reviewing', 'shortlisted', 'interview', 'offered', 'hired', 'rejected'])
                ->default('new');
            $table->text('internal_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['career_id', 'status']);
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
