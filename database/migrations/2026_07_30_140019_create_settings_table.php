<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value site configuration edited from the CMS (company details, social
 * links, homepage copy, mail routing). `group` drives the tabbed settings UI;
 * `type` drives which form control is rendered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->enum('type', ['string', 'text', 'richtext', 'boolean', 'integer', 'json', 'image', 'file', 'select', 'color'])
                ->default('string');
            $table->string('label');
            $table->string('description')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
