<?php

namespace App\Notifications;

use App\Services\OtpService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The verification email, carrying a six-digit code as well as the usual
 * signed link.
 *
 * Both confirm the same address, and one message covers both: the link is the
 * quicker path on a desktop, the code is the reliable one on a phone, where
 * the link often opens in a browser with no session and quietly fails. The
 * recipient picks whichever works rather than having to ask for a second
 * email to get the other.
 *
 * The code is minted by OtpService before this notification is constructed —
 * see User::sendEmailVerificationNotification — so the database holds only a
 * hash of it, exactly as for a code sent on its own.
 */
class VerifyEmailWithCode extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $code) {}

    /**
     * Built here rather than in the parent's buildMailMessage so the greeting
     * can use the recipient's name.
     */
    public function toMail($notifiable): MailMessage
    {
        $company = setting('company.name');

        return (new MailMessage)
            ->subject('Verify your email address — '.$company)
            ->greeting($notifiable->name ? 'Hello '.$notifiable->name.',' : 'Hello,')
            ->line('Please confirm this email address to finish setting up your account. Either way below works — you only need one.')
            ->line('**Enter this code:**')
            // Spaced so it is easy to read off a phone screen without
            // misreading a digit.
            ->line('## '.trim(chunk_split($this->code, 3, ' ')))
            ->line('Type it in at '.route('otp.verify').' while signed in. The code expires in '
                .OtpService::TTL_MINUTES.' minutes and can be used once.')
            ->line('**Or follow the link:**')
            ->action('Verify email address', $this->verificationUrl($notifiable))
            ->line('**Never share the code with anyone.** Nobody from '.$company
                .' will ask you for it — not by phone, not by email.')
            ->line('If you did not create an account, you can ignore this message; nothing will change.')
            ->replyTo(setting('contact.email_general', config('mail.from.address')), $company)
            ->salutation('Regards,'."\n".$company);
    }
}
