<?php

namespace App\Models;

use App\Models\Concerns\HasExpiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** A course an employee is booked on or has completed, and any certificate it led to. */
class EmployeeTraining extends Model
{
    use HasExpiry;
    use LogsActivity;
    use SoftDeletes;

    public const CATEGORIES = [
        'induction' => 'Induction',
        'safety' => 'Health & safety',
        'technical' => 'Technical',
        'compliance' => 'Compliance',
        'medical' => 'Medical / first aid',
        'leadership' => 'Leadership & management',
        'soft_skills' => 'Soft skills',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'employee_id', 'title', 'category', 'provider', 'start_date', 'end_date', 'hours', 'cost',
        'status', 'result', 'certificate_number', 'certificate_expiry', 'certificate_path', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'certificate_expiry' => 'date',
            'hours' => 'decimal:1',
            'cost' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'certificate_expiry', 'employee_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('training');
    }

    public function expiryColumn(): string
    {
        return 'certificate_expiry';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /** Only training for staff this user may see. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('employee', fn (Builder $q) => $q->visibleToStaff($user));
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'in_progress' => 'info',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    }
}
