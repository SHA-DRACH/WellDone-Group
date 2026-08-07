<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks an account whose password was set by somebody else.
 *
 * When HR types an employee's first password, that password is known to at
 * least two people. The flag forces the employee to replace it before they
 * can reach anything else, so the shared secret has the shortest possible
 * life. Accounts that set their own password through the emailed link are
 * never flagged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
