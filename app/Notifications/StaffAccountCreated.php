<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\Navigation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Welcome email sent when an administrator creates a staff account.
 *
 * No password is ever emailed. The message carries a signed password-reset
 * link instead, so the new user chooses their own credential and the plain
 * text never exists anywhere. Email verification is handled separately by
 * Laravel's MustVerifyEmail flow, which fires on the same event.
 */
class StaffAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // A reset token doubles as a "set your first password" token.
        $token = Password::broker()->createToken($this->user);
        $url = route('password.reset', ['token' => $token, 'email' => $this->user->email]);

        $roles = $this->user->getRoleNames()->implode(', ');

        $mail = (new MailMessage)
            ->subject('Your '.setting('company.name').' account is ready')
            ->greeting('Welcome, '.$this->user->name.'.')
            ->line('An account has been created for you on the '.setting('company.name').' control panel.')
            ->line('**Email:** '.$this->user->email)
            ->line('**Role:** '.($roles ?: 'Not assigned'));

        if ($this->user->division) {
            $mail->line('**Division:** '.$this->user->division->name)
                ->line('You will land on the '.$this->user->division->name.' dashboard when you sign in.');
        }

        return $mail
            ->action('Choose your password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire', 60).' minutes. If it lapses, use "Forgotten?" on the sign-in page to request a new one.')
            ->line('If you were not expecting this, please tell your ICT manager and do not use the link.')
            ->salutation('Regards,'."\n".setting('company.name'));
    }
}
