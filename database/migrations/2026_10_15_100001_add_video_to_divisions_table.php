<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A short introductory film for each division's public page.
 *
 * Either an uploaded MP4 (`video_path`) or a YouTube/Vimeo link
 * (`video_url`); an upload wins when both are present. The length of an
 * upload is read when it arrives and kept, so the page can show it without
 * opening the file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('brochure_path');
            $table->string('video_url')->nullable()->after('video_path');
            $table->string('video_poster')->nullable()->after('video_url');
            $table->unsignedSmallInteger('video_duration')->nullable()->after('video_poster');
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'video_url', 'video_poster', 'video_duration']);
        });
    }
};
