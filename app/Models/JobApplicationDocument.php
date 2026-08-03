<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A supporting document attached to an application — certificates, a cover
 * letter, identification. Stored on the private disk alongside the résumé and
 * streamed through an authorised route, never linked directly.
 */
class JobApplicationDocument extends Model
{
    use HasFactory;

    public const TYPES = ['cv', 'cover_letter', 'certificate', 'reference', 'identification', 'other'];

    protected $fillable = [
        'job_application_id', 'type', 'path', 'original_name', 'mime_type', 'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (JobApplicationDocument $doc) => Storage::disk('local')->delete($doc->path));
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'cv' => 'CV / Résumé',
            'cover_letter' => 'Cover letter',
            'certificate' => 'Certificate',
            'reference' => 'Reference',
            'identification' => 'Identification',
            default => 'Other document',
        };
    }

    public function getHumanSizeAttribute(): string
    {
        return \App\Support\Format::fileSize((int) $this->size);
    }
}
