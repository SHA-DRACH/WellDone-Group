<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO metadata. A row either attaches to a model (polymorphic — a project, an
 * article, a division) or to a static route name such as `site.about`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('seoable');
            $table->string('route_name')->nullable();
            $table->string('title')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('keywords', 500)->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index, follow');
            $table->json('schema_markup')->nullable();
            $table->timestamps();

            $table->unique('route_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo');
    }
};
