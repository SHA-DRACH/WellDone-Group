<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use App\Support\DivisionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A candidate's submission against a vacancy.
 *
 * Résumés live on the private `local` disk and are streamed through an
 * authorised controller action — they are never exposed under /storage.
 */
class JobApplication extends Model
{
    use HasFactory;
    use ScopedToDivision;

    /**
     * An application carries no division of its own — it inherits the one on
     * the vacancy applied for. Both halves of the trait are therefore
     * overridden to reach through the relation.
     */
    public function scopeWithinDivisionOf(Builder $query, ?User $user): Builder
    {
        $id = DivisionScope::idFor($user);

        if ($id === null) {
            return $query;
        }

        // A speculative application against no vacancy belongs to nobody
        // until recruitment routes it, so it is not shown to any division.
        return $query->whereHas('career', fn (Builder $q) => $q->where('division_id', $id));
    }

    public function divisionScopeId(): ?int
    {
        return $this->career?->division_id;
    }

    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }

    /** The recruitment pipeline, in the order a candidate moves through it. */
    public const STATUSES = [
        'new', 'under_review', 'shortlisted', 'interview_scheduled',
        'interview_completed', 'offered', 'hired', 'rejected', 'withdrawn',
    ];

    /** Stages from which a candidate can still progress. */
    public const OPEN_STATUSES = [
        'new', 'under_review', 'shortlisted', 'interview_scheduled',
        'interview_completed', 'offered',
    ];

    protected $fillable = [
        'career_id', 'reference', 'user_id', 'employee_id', 'full_name', 'email', 'phone', 'country', 'city',
        'cover_letter', 'resume_path', 'resume_original_name', 'portfolio_url',
        'linkedin_url', 'years_experience', 'highest_qualification', 'expected_salary',
        'available_from', 'status', 'internal_notes', 'reviewed_by', 'reviewed_at',
        'ip_address',
        'interview_at', 'interview_location', 'interview_mode', 'interview_notes',
        'interview_score', 'offered_salary', 'offer_start_date', 'offered_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'available_from' => 'date',
            'reviewed_at' => 'datetime',
            'years_experience' => 'integer',
            'interview_at' => 'datetime',
            'offered_at' => 'datetime',
            'offer_start_date' => 'date',
            'interview_score' => 'integer',
            'offered_salary' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JobApplication $application): void {
            $application->reference ??= static::generateReference();
        });

        static::deleted(function (JobApplication $application): void {
            if ($application->resume_path) {
                Storage::disk('local')->delete($application->resume_path);
            }
        });
    }

    public static function generateReference(): string
    {
        return 'WDG-APP-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** The registered account that submitted this application, if any. */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The employee record created when this candidate was hired. */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Certificates, cover letters and other supporting uploads. */
    public function documents(): HasMany
    {
        return $this->hasMany(JobApplicationDocument::class);
    }

    /** Still moving through the pipeline — neither rejected nor withdrawn. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    /** Interviews coming up, soonest first. */
    public function scopeUpcomingInterviews(Builder $query): Builder
    {
        return $query->whereNotNull('interview_at')
            ->where('interview_at', '>=', now())
            ->orderBy('interview_at');
    }

    /** True once this candidate has been converted into an employee record. */
    public function getIsConvertedAttribute(): bool
    {
        return $this->employee_id !== null;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            $status && in_array($status, self::STATUSES, true),
            fn (Builder $q) => $q->where('status', $status)
        );
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('full_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('reference', 'like', $like));
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'primary',
            'under_review' => 'info',
            'shortlisted' => 'warning',
            'interview_scheduled', 'interview_completed' => 'warning',
            'offered' => 'info',
            'hired' => 'success',
            'rejected' => 'danger',
            'withdrawn' => 'secondary',
            default => 'secondary',
        };
    }

    /** Position of this candidate in the pipeline, for a progress indicator. */
    public function getStageNumberAttribute(): int
    {
        return match ($this->status) {
            'new' => 1,
            'under_review' => 2,
            'shortlisted' => 3,
            'interview_scheduled' => 4,
            'interview_completed' => 5,
            'offered' => 6,
            'hired' => 7,
            default => 0,
        };
    }
}
