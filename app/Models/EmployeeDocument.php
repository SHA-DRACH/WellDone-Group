<?php

namespace App\Models;

use App\Models\Concerns\HasExpiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A document on an employee's file — contract, ID copy, permit.
 *
 * The file is on the private disk and only ever streamed by
 * EmployeeDocumentController after a policy check. `file_path` is hidden from
 * serialisation for the same reason the employee's ID number is.
 */
class EmployeeDocument extends Model
{
    use HasExpiry;
    use LogsActivity;
    use SoftDeletes;

    public const TYPES = [
        'contract' => 'Employment contract',
        'national_id' => 'National ID',
        'passport' => 'Passport',
        'work_permit' => 'Work / residence permit',
        'drivers_licence' => 'Driver\'s licence',
        'certificate' => 'Qualification / certificate',
        'medical' => 'Medical certificate',
        'cv' => 'CV / résumé',
        'reference' => 'Reference letter',
        'disciplinary' => 'Disciplinary record',
        'other' => 'Other',
    ];

    protected $fillable = [
        'employee_id', 'type', 'title', 'document_number', 'issue_date', 'expiry_date',
        'file_path', 'original_name', 'file_size', 'notes', 'uploaded_by',
    ];

    protected $hidden = ['file_path', 'document_number'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'file_size' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'title', 'expiry_date', 'employee_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('employee-document');
    }

    public function expiryColumn(): string
    {
        return 'expiry_date';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('employee', fn (Builder $q) => $q->visibleToStaff($user));
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
