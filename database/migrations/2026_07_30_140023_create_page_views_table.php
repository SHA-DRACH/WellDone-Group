<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight first-party page-view log powering the dashboard's traffic
 * widget until a full analytics provider is integrated.
 *
 * Visitor IPs are stored as a salted hash, never in the clear, so the table
 * can be retained for trend analysis without holding personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500);
            $table->string('route_name')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('visitor_hash', 64)->index();
            $table->string('device', 20)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index('viewed_at');
            $table->index(['route_name', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
