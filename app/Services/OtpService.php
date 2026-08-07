<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpCodeIssued;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Issues and verifies six-digit one-time codes sent by email.
 *
 * Three deliberate choices:
 *
 *  - The code is hashed before storage, exactly as a password would be. A
 *    database dump does not hand anyone a set of working codes.
 *  - Codes are short-lived and attempt-limited. Six digits is only a million
 *    combinations, which is trivially brute-forceable given unlimited tries;
 *    five attempts inside ten minutes is not.
 *  - Issuing is throttled per address, so the endpoint cannot be used to
 *    flood someone's inbox.
 */
class OtpService
{
    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const PURPOSE_PASSWORD_CHANGE = 'password_change';

    public const LENGTH = 6;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Most codes that may be issued to one address within the window. */
    public const MAX_PER_WINDOW = 5;

    public const WINDOW_MINUTES = 15;

    /**
     * Create a code and email it on its own, and return the record.
     */
    public function issue(string $email, string $purpose, ?User $user = null, ?string $ip = null): OtpCode
    {
        ['code' => $code, 'record' => $record] = $this->mint($email, $purpose, $user, $ip);

        // The plain code exists only here, in memory, on its way to the email.
        Notification::route('mail', $record->email)->notify(new OtpCodeIssued($code, $purpose, $user?->name));

        return $record;
    }

    /**
     * Create a code and return it in plain text alongside its record, without
     * sending anything.
     *
     * For the callers that carry the code in an email of their own — the
     * verification message puts the code beside the link, so one email covers
     * both ways of confirming an address.
     *
     * Any earlier unused code for the same address and purpose is spent
     * first, so only the newest code ever works.
     *
     * @return array{code: string, record: OtpCode}
     */
    public function mint(string $email, string $purpose, ?User $user = null, ?string $ip = null): array
    {
        $email = Str::lower(trim($email));

        OtpCode::query()
            ->where('email', $email)
            ->forPurpose($purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        $record = OtpCode::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'ip_address' => $ip,
        ]);

        return ['code' => $code, 'record' => $record];
    }

    /**
     * Check a submitted code.
     *
     * On success the code is spent, so it cannot be replayed.
     *
     * @return array{ok: bool, message: ?string, otp: ?OtpCode}
     */
    public function verify(string $email, string $purpose, string $submitted): array
    {
        $email = Str::lower(trim($email));

        $otp = OtpCode::query()
            ->where('email', $email)
            ->forPurpose($purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return $this->failure('We could not find a code for that address. Please request a new one.');
        }

        if ($otp->is_expired) {
            return $this->failure('That code has expired. Please request a new one.');
        }

        if ($otp->is_locked) {
            return $this->failure('Too many incorrect attempts. Please request a new code.');
        }

        if (! Hash::check(trim($submitted), $otp->code_hash)) {
            $otp->increment('attempts');

            $remaining = max(0, self::MAX_ATTEMPTS - $otp->attempts);

            return $this->failure($remaining > 0
                ? "That code is not correct. {$remaining} attempt(s) remaining."
                : 'Too many incorrect attempts. Please request a new code.');
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return ['ok' => true, 'message' => null, 'otp' => $otp];
    }

    /**
     * Whether another code may be issued to this address right now.
     *
     * Prevents the send endpoint being used to bombard an inbox.
     */
    public function canIssue(string $email, string $purpose): bool
    {
        return OtpCode::query()
            ->where('email', Str::lower(trim($email)))
            ->forPurpose($purpose)
            ->where('created_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->count() < self::MAX_PER_WINDOW;
    }

    /** Housekeeping: drop codes that are long spent or expired. */
    public function prune(int $days = 7): int
    {
        return OtpCode::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * A numeric code of fixed length, from a cryptographically secure source.
     * `random_int` rather than `rand`, because the value guards an account.
     */
    protected function generateCode(): string
    {
        $max = (10 ** self::LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::LENGTH, '0', STR_PAD_LEFT);
    }

    protected function failure(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'otp' => null];
    }
}
