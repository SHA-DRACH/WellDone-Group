<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff profile fields plus the account-state and audit columns the admin
 * panel needs (deactivation, last sign-in, optional 2FA secret).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('position')->nullable()->after('phone');
            $table->string('department')->nullable()->after('position');
            $table->foreignId('division_id')->nullable()->after('department')
                ->constrained('divisions')->nullOnDelete();
            $table->string('avatar')->nullable()->after('division_id');
            $table->text('bio')->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('bio');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
            $table->text('two_factor_secret')->nullable()->after('last_login_ip');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropColumn([
                'phone', 'position', 'department', 'avatar', 'bio', 'is_active',
                'last_login_at', 'last_login_ip', 'two_factor_secret',
                'two_factor_recovery_codes', 'two_factor_confirmed_at', 'deleted_at',
            ]);
        });
    }
};
