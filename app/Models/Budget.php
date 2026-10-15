<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A spending limit for a year, narrowed by any of division, department and
 * expense category. Leaving one empty means "all of them": a budget with a
 * division and no category caps everything that division spends.
 */
class Budget extends Model
{
    use LogsActivity;
    use ScopedToDivision;

    protected $fillable = [
        'name', 'fiscal_year', 'division_id', 'department_id', 'expense_category_id', 'amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'fiscal_year', 'amount', 'division_id', 'department_id', 'expense_category_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('budget');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** The approved and paid expenses this budget covers. */
    public function expensesQuery(): Builder
    {
        return Expense::query()
            ->counted()
            ->whereYear('expense_date', $this->fiscal_year)
            ->when($this->division_id, fn (Builder $q) => $q->where('division_id', $this->division_id))
            ->when($this->department_id, fn (Builder $q) => $q->where('department_id', $this->department_id))
            ->when($this->expense_category_id, fn (Builder $q) => $q->where('expense_category_id', $this->expense_category_id));
    }

    public function actual(): float
    {
        return round((float) $this->expensesQuery()->sum('amount'), 2);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('fiscal_year')->orderBy('name');
    }

    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }
}
