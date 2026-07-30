<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job openings and internships published on the careers portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship', 'temporary'])
                ->default('full_time');
            $table->enum('experience_level', ['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])
                ->default('mid');
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->longText('benefits')->nullable();
            $table->string('salary_range')->nullable();
            $table->unsignedInteger('positions_available')->default(1);
            $table->date('opens_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'closes_at']);
            $table->index(['division_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
