<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public const STATUSES = ['new', 'reviewing', 'shortlisted', 'interview', 'offered', 'hired', 'rejected'];

    protected $fillable = [
        'career_id', 'reference', 'full_name', 'email', 'phone', 'country', 'city',
        'cover_letter', 'resume_path', 'resume_original_name', 'portfolio_url',
        'linkedin_url', 'years_experience', 'highest_qualification', 'expected_salary',
        'available_from', 'status', 'internal_notes', 'reviewed_by', 'reviewed_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'available_from' => 'date',
            'reviewed_at' => 'datetime',
            'years_experience' => 'integer',
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
            'reviewing' => 'info',
            'shortlisted', 'interview' => 'warning',
            'offered', 'hired' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
