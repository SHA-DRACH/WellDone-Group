<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `video` (uploaded MP4) and `video_url` (YouTube/Vimeo link)
 * setting types used by the homepage welcome video.
 */
return new class extends Migration
{
    private const BASE = ['string', 'text', 'richtext', 'boolean', 'integer', 'json', 'image', 'file', 'select', 'color'];

    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('type', [...self::BASE, 'video', 'video_url'])->default('string')->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('type', self::BASE)->default('string')->change();
        });
    }
};
