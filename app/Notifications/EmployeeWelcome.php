<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Sent when HR creates a portal login for a new employee.
 *
 * Normally it carries a link to set a password rather than a temporary one. A
 * password written into an email stays in that inbox — and any mail server it
 * passed through — indefinitely; a signed link expires.
 *
 * When HR set the password themselves (for staff with no reliable email), the
 * link is omitted: it would be a second, unnecessary way into the account, and
 * the password itself is never written here either — HR hands it over in
 * person.
 */
class EmployeeWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public bool $passwordSetByHr = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Welcome to '.setting('company.name'))
            ->greeting('Welcome, '.$this->employee->first_name.'.')
            ->line('Your employee record has been created and you now have access to the staff portal.')
            ->line('**Employee number:** '.$this->employee->employee_number)
            ->line('**Position:** '.($this->employee->position?->title ?? 'To be confirmed'))
            ->line('**Department:** '.($this->employee->department?->name ?? 'To be confirmed'))
            ->line('**Sign in with:** '.$notifiable->email);

        if ($this->passwordSetByHr) {
            $message
                ->line('Human Resources has set your first password and will give it to you directly. '
                    .'It is not written in this email.')
                ->action('Go to the staff portal', route('login'))
                ->line('You will be asked to choose your own password the first time you sign in — '
                    .'the one you were given stops working at that point.');
        } else {
            $token = Password::broker()->createToken($notifiable);

            $message
                ->action('Set your password', route('password.reset', [
                    'token' => $token, 'email' => $notifiable->email,
                ]))
                ->line('This link expires in '.config('auth.passwords.users.expire', 60)
                    .' minutes. If it lapses, use "Forgotten?" on the sign-in page to request a new one.');
        }

        return $message
            ->line('Once signed in you can view your record, keep your contact details current, and read company announcements.')
            ->line('If anything on your record is wrong, please tell Human Resources — some details can only be changed by them.')
            ->replyTo(setting('contact.email_hr', setting('contact.email_general', config('mail.from.address'))), setting('company.name'))
            ->salutation('Kind regards,'."\n".'Human Resources'."\n".setting('company.name'));
    }
}
