<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Routes a contact enquiry to a business division.
 *
 * Division staff only see enquiries for their own division, so an enquiry
 * needs to carry one. Nullable: general enquiries belong to no division and
 * are visible to group-level staff only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('user_id')
                ->constrained('divisions')->nullOnDelete();
            $table->index(['division_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
        });
    }
};
