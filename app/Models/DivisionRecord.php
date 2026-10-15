<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use App\Support\DivisionRecords\RecordSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One line in a division's operations log.
 *
 * What the line holds depends on the division — see RecordSchema. The model
 * only knows that there is a date, a reference, the division-specific
 * `data`, and notes.
 */
class DivisionRecord extends Model
{
    use LogsActivity;
    use ScopedToDivision;
    use SoftDeletes;

    protected $fillable = [
        'division_id', 'reference', 'record_date', 'data', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DivisionRecord $record): void {
            $record->reference ??= static::nextReference($record->division_id, $record->record_date?->year ?? now()->year);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference', 'record_date', 'data', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('division-record');
    }

    /**
     * MQ-2026-00001 — per division, per year. Taken from the highest number
     * issued so far, deleted rows included, so a number is never reused.
     */
    public static function nextReference(int $divisionId, int $year): string
    {
        $division = Division::withTrashed()->find($divisionId);
        $prefix = ($division ? RecordSchema::prefixFor($division) : 'DV').'-'.$year.'-';

        $last = static::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(reference) DESC, reference DESC')
            ->value('reference');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------- Relationships

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------- Scopes

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('record_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('record_date', '<=', $to));
    }

    /**
     * Free-text search across the JSON fields. Matching on the encoded JSON
     * keeps it database-agnostic; for a log of this size it is quick enough.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
            ->where('reference', 'like', '%'.$term.'%')
            ->orWhere('data', 'like', '%'.$term.'%')
            ->orWhere('notes', 'like', '%'.$term.'%')));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('record_date')->orderByDesc('id');
    }

    // -------------------------------------------------- Accessors

    public function field(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /** Division logs belong to a division; there is no group-wide entry. */
    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }
}
