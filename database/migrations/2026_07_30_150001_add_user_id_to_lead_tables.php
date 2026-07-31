<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links quotation requests, job applications and contact enquiries to the
 * registered account that submitted them, so a client can see their own
 * history in the account portal.
 *
 * Nullable throughout: historic rows predate registration, and an
 * administrator may still record a lead that arrived by phone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('reference')
                ->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('reference')
                ->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['quote_requests', 'job_applications', 'contact_messages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
