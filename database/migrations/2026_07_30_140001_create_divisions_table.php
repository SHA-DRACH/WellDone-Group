<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business divisions (Mining & Quarry, Procurement & Logistics, Medical
 * Equipment, ICT ...). Divisions are data rather than code so that new
 * subsidiaries can be added from the CMS without a deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->string('icon')->default('fa-solid fa-building');
            $table->string('accent_color', 20)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('capabilities')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('brochure_path')->nullable();
            $table->json('highlights')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
