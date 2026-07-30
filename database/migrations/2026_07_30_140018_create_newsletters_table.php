<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsletter subscribers. Double opt-in: a subscriber is only mailable once
 * `confirmed_at` is set, and `token` backs both confirm and unsubscribe links.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source')->default('website');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['confirmed_at', 'unsubscribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
