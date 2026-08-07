<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the careers inbox into a recruitment pipeline.
 *
 * Adds interview scheduling, the offer stage, and a link back to the employee
 * record created when a candidate is hired — which is what lets the system
 * generate an employee profile from the application instead of re-keying it.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * The status column is an enum, so widening it needs a raw statement —
         * `interview` becomes the more precise `interview_scheduled` and
         * `interview_completed`, and `under_review` replaces `reviewing`.
         * Existing rows are migrated below.
         *
         * MODIFY COLUMN is MySQL's; SQLite has no such statement and stores an
         * enum as a plain string, so it needs no widening at all. Guarding by
         * driver rather than assuming MySQL is what lets the test suite run on
         * an in-memory SQLite database.
         */
        if ($this->supportsEnumModification()) {
            DB::statement("
                ALTER TABLE job_applications
                MODIFY COLUMN status ENUM(
                    'new',
                    'reviewing',
                    'under_review',
                    'shortlisted',
                    'interview',
                    'interview_scheduled',
                    'interview_completed',
                    'offered',
                    'hired',
                    'rejected',
                    'withdrawn'
                ) NOT NULL DEFAULT 'new'
            ");
        }

        DB::table('job_applications')->where('status', 'reviewing')->update(['status' => 'under_review']);
        DB::table('job_applications')->where('status', 'interview')->update(['status' => 'interview_scheduled']);

        // Drop the two superseded values now that no row uses them.
        if ($this->supportsEnumModification()) {
            DB::statement("
                ALTER TABLE job_applications
                MODIFY COLUMN status ENUM(
                    'new',
                    'under_review',
                    'shortlisted',
                    'interview_scheduled',
                    'interview_completed',
                    'offered',
                    'hired',
                    'rejected',
                    'withdrawn'
                ) NOT NULL DEFAULT 'new'
            ");
        }

        Schema::table('job_applications', function (Blueprint $table) {
            $table->timestamp('interview_at')->nullable()->after('reviewed_at');
            $table->string('interview_location')->nullable()->after('interview_at');
            $table->enum('interview_mode', ['in_person', 'phone', 'video'])->default('in_person')->after('interview_location');
            $table->text('interview_notes')->nullable()->after('interview_mode');
            $table->unsignedTinyInteger('interview_score')->nullable()->after('interview_notes');

            $table->decimal('offered_salary', 15, 2)->nullable()->after('interview_score');
            $table->date('offer_start_date')->nullable()->after('offered_salary');
            $table->timestamp('offered_at')->nullable()->after('offer_start_date');
            $table->text('rejection_reason')->nullable()->after('offered_at');

            // Set when the candidate is converted into an employee record.
            $table->foreignId('employee_id')->nullable()->after('reference')
                ->constrained('employees')->nullOnDelete();

            $table->index('interview_at');
        });

        // Supporting documents beyond the résumé: certificates, cover letters.
        Schema::create('job_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->enum('type', ['cv', 'cover_letter', 'certificate', 'reference', 'identification', 'other'])
                ->default('other');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['job_application_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_application_documents');

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn([
                'interview_at', 'interview_location', 'interview_mode', 'interview_notes',
                'interview_score', 'offered_salary', 'offer_start_date', 'offered_at',
                'rejection_reason',
            ]);
        });

        if ($this->supportsEnumModification()) {
            DB::statement("
                ALTER TABLE job_applications
                MODIFY COLUMN status ENUM('new','reviewing','shortlisted','interview','offered','hired','rejected')
                NOT NULL DEFAULT 'new'
            ");
        }
    }

    /** MySQL and MariaDB only; every other driver stores an enum as a string. */
    protected function supportsEnumModification(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }
};
