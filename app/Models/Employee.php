<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A person the company employs.
 *
 * Deliberately separate from User: an account is a way to sign in, an
 * employee is a person on the payroll. A plant operator may never log in; a
 * registered client is a user but not an employee. `user_id` links the two
 * when portal access is needed.
 *
 * National ID, passport and date of birth are hidden from array/JSON output
 * so they cannot leak through a careless `toArray()` in a response.
 */
class Employee extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use LogsActivity;
    use SoftDeletes;

    public const STATUSES = [
        'probation', 'active', 'suspended', 'on_leave',
        'resigned', 'terminated', 'retired',
    ];

    protected $fillable = [
        'employee_number', 'user_id', 'job_application_id',
        'first_name', 'middle_name', 'last_name', 'photo', 'gender', 'date_of_birth',
        'nationality', 'national_id', 'passport_number', 'marital_status', 'blood_group',
        'email', 'personal_email', 'phone', 'alternative_phone',
        'residential_address', 'city', 'country',
        'department_id', 'division_id', 'position_id', 'employment_type_id',
        'reporting_manager_id', 'date_hired', 'confirmation_date', 'contract_expiry',
        'employment_status', 'exit_date', 'work_location', 'notes', 'is_active',
    ];

    protected $hidden = ['national_id', 'passport_number', 'date_of_birth'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_hired' => 'date',
            'confirmation_date' => 'date',
            'contract_expiry' => 'date',
            'exit_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            $employee->employee_number ??= static::nextEmployeeNumber();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_number', 'first_name', 'last_name', 'department_id',
                'position_id', 'employment_status', 'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('employee');
    }

    /**
     * Sequential staff number, e.g. WDG-EMP-0001.
     *
     * Derived from the highest existing number rather than a row count, so
     * deleting an employee never causes a number to be reissued.
     */
    public static function nextEmployeeNumber(string $prefix = 'WDG-EMP-'): string
    {
        $last = static::withTrashed()
            ->where('employee_number', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(employee_number) DESC, employee_number DESC')
            ->value('employee_number');

        $next = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------- Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_manager_id');
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function bankDetails(): HasOne
    {
        return $this->hasOne(EmployeeBankDetail::class);
    }

    // -------------------------------------------------- Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Currently employed — excludes leavers of any kind. */
    public function scopeEmployed(Builder $query): Builder
    {
        return $query->whereIn('employment_status', ['probation', 'active', 'suspended', 'on_leave']);
    }

    public function scopeInDepartment(Builder $query, $departmentId): Builder
    {
        return $query->when($departmentId, fn (Builder $q) => $q->where('department_id', $departmentId));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('employee_number', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like));
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('last_name')->orderBy('first_name');
    }

    // -------------------------------------------------- Accessors

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' ');
    }

    public function getInitialsAttribute(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->photo, asset('images/placeholders/avatar.svg'));
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->employment_status)->replace('_', ' ')->title()->toString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->employment_status) {
            'active' => 'success',
            'probation' => 'info',
            'on_leave' => 'warning',
            'suspended' => 'warning',
            'resigned', 'retired' => 'secondary',
            'terminated' => 'danger',
            default => 'secondary',
        };
    }

    /** Whole years of service, or null when the hire date is unknown. */
    public function getYearsOfServiceAttribute(): ?int
    {
        if (! $this->date_hired) {
            return null;
        }

        return $this->date_hired->diffInYears($this->exit_date ?? now());
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /** True when a fixed-term contract ends within the given window. */
    public function contractExpiringWithin(int $days = 60): bool
    {
        return $this->contract_expiry !== null
            && $this->contract_expiry->isFuture()
            && $this->contract_expiry->diffInDays(now()) <= $days;
    }
}
