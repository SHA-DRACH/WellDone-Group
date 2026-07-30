<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services offered under each division. Services double as the selectable
 * line items on the "Request a Quote" form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('fa-solid fa-circle-check');
            $table->string('image')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_quotable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['division_id', 'is_active', 'sort_order']);
            $table->index('is_featured');
            $table->index('is_quotable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
