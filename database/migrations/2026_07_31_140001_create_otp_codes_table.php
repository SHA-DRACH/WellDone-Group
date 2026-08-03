<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-time codes emailed to a user.
 *
 * The code itself is never stored — only a hash of it, exactly as with a
 * password. Anyone reading this table (a backup, a compromised database
 * dump) still cannot use the codes in it.
 *
 * `attempts` supports locking a code after repeated wrong guesses, which is
 * what stops a six-digit code being brute-forced within its lifetime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            // Recorded separately so a code can be issued before an account
            // is resolved, and so it is verified against the same address.
            $table->string('email');

            $table->enum('purpose', ['email_verification', 'password_reset', 'password_change'])
                ->default('email_verification');

            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['email', 'purpose', 'consumed_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
