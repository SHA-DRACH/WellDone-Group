<?php

namespace App\Rules;

use App\Support\Video\Mp4Duration;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * An uploaded video must run between a minimum and maximum length.
 *
 * Checked on the server from the file itself; the length the browser reports
 * is only used to warn early. Half a second of leeway either side, because
 * an export set to "one minute" routinely comes out at 60.02 seconds.
 */
class VideoLength implements ValidationRule
{
    public function __construct(
        public readonly int $minSeconds = 30,
        public readonly int $maxSeconds = 60,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $seconds = Mp4Duration::seconds($value->getRealPath());

        if ($seconds === null) {
            $fail('The length of this video could not be read. Please upload a standard MP4 file.');

            return;
        }

        if ($seconds < $this->minSeconds - 0.5 || $seconds > $this->maxSeconds + 0.5) {
            $fail(sprintf(
                'The video must be between %s and %s long — this one runs %s.',
                self::human($this->minSeconds),
                self::human($this->maxSeconds),
                self::human((int) round($seconds)),
            ));
        }
    }

    public static function human(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return match (true) {
            $minutes === 0 => $rest.' seconds',
            $rest === 0 => $minutes === 1 ? '1 minute' : $minutes.' minutes',
            default => sprintf('%d:%02d', $minutes, $rest),
        };
    }

    /** Largest upload accepted, in kilobytes (100 MB). */
    public const MAX_KB = 102400;

    /** The validation rules for an uploaded video field, in one place. */
    public static function rules(int $min = 30, int $max = 60): array
    {
        return [
            'nullable',
            'file',
            'mimetypes:video/mp4,video/x-m4v,video/quicktime',
            'max:'.self::MAX_KB,
            new self($min, $max),
        ];
    }
}
