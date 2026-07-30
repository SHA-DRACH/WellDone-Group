<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completed and ongoing projects, filterable on the public site by division
 * and status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('scope_of_work')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('location')->nullable();
            $table->string('client_display_name')->nullable();
            $table->boolean('show_client')->default(true);
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('show_value')->default(false);
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'on_hold'])->default('completed');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(100);
            $table->json('key_facts')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'status']);
            $table->index(['division_id', 'is_active']);
            $table->index(['is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
