<?php

namespace App\Notifications;

use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a one-time code.
 *
 * The code is passed in memory and never persisted in readable form — the
 * database holds only a hash of it.
 */
class OtpCodeIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $code,
        protected string $purpose,
        protected ?string $name = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $intro] = match ($this->purpose) {
            'password_reset' => [
                'Your password reset code',
                'Use the code below to reset your password.',
            ],
            'password_change' => [
                'Confirm your password change',
                'Use the code below to confirm the change to your password.',
            ],
            default => [
                'Your verification code',
                'Use the code below to confirm your email address.',
            ],
        };

        return (new MailMessage)
            ->subject($subject.' — '.setting('company.name'))
            ->greeting($this->name ? 'Hello '.$this->name.',' : 'Hello,')
            ->line($intro)
            // Spaced so it is easy to read off a phone screen without
            // misreading a digit.
            ->line('## '.trim(chunk_split($this->code, 3, ' ')))
            ->line('This code expires in '.OtpService::TTL_MINUTES.' minutes and can be used once.')
            ->line('**Never share it with anyone.** Nobody from '.setting('company.name')
                .' will ask you for it — not by phone, not by email.')
            ->line('If you did not request this, you can ignore this message; nothing will change.')
            ->replyTo(setting('contact.email_general', config('mail.from.address')), setting('company.name'))
            ->salutation('Regards,'."\n".setting('company.name'));
    }
}
