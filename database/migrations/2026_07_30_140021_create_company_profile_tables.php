<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting content for the About Us section: leadership, accreditations,
 * company history milestones and CSR initiatives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('position');
            $table->string('department')->nullable();
            $table->text('short_bio')->nullable();
            $table->longText('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_leadership')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_leadership', 'sort_order']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('file_path')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->string('year', 10);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->default('fa-solid fa-flag');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('csr_initiatives', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('focus_area')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->string('location')->nullable();
            $table->year('year')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('question');
            $table->longText('answer');
            $table->string('category')->default('general');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('csr_initiatives');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('team_members');
    }
};
