<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organisations WellDone has delivered work for. Referenced by projects and
 * testimonials; `is_public` governs whether the name may be shown publicly
 * (some contracts prohibit naming the client).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
