<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each division's day-to-day log — production, shipments, stock, jobs.
 *
 * The fields differ per division (see App\Support\DivisionRecords\
 * RecordSchema), so they are held as JSON in `data` rather than as columns
 * four divisions would mostly leave empty. What every entry shares — the
 * division, the date, a reference and who keyed it — are real columns,
 * indexed for the date-range filters and the monthly totals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->date('record_date');
            $table->json('data');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['division_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_records');
    }
};
