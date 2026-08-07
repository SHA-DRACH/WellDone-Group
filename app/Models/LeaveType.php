<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A category of leave — annual, sick, maternity, compassionate.
 *
 * The rules an employee is held to live here rather than in the request
 * handling, so HR can add a type without anyone touching code.
 */
class LeaveType extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'days_per_year', 'is_paid',
        'requires_document', 'document_required_after_days', 'max_carry_forward_days',
        'applies_to', 'minimum_service_days', 'colour', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'decimal:2',
            'max_carry_forward_days' => 'decimal:2',
            'is_paid' => 'boolean',
            'requires_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'days_per_year', 'is_paid', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('leave_type');
    }

    // -------------------------------------------------- Relationships

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // -------------------------------------------------- Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Types this employee is eligible for — gender restrictions and the
     * qualifying period applied.
     */
    public function scopeAvailableTo(Builder $query, Employee $employee): Builder
    {
        $servedDays = $employee->date_hired ? $employee->date_hired->diffInDays(now()) : 0;

        return $query
            ->where(function (Builder $q) use ($employee) {
                $q->where('applies_to', 'all');

                if (in_array($employee->gender, ['male', 'female'], true)) {
                    $q->orWhere('applies_to', $employee->gender);
                }
            })
            ->where('minimum_service_days', '<=', $servedDays);
    }

    // -------------------------------------------------- Helpers

    /** Whether a request of this length must carry supporting evidence. */
    public function requiresDocumentFor(float $days): bool
    {
        return $this->requires_document && $days > $this->document_required_after_days;
    }
}
