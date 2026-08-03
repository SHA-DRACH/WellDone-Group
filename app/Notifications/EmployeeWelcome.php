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
 * Carries a link to set a password rather than a temporary one. A password
 * written into an email stays in that inbox — and any mail server it passed
 * through — indefinitely; a signed link expires.
 */
class EmployeeWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Employee $employee) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $token = Password::broker()->createToken($notifiable);
        $url = route('password.reset', ['token' => $token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject('Welcome to '.setting('company.name'))
            ->greeting('Welcome, '.$this->employee->first_name.'.')
            ->line('Your employee record has been created and you now have access to the staff portal.')
            ->line('**Employee number:** '.$this->employee->employee_number)
            ->line('**Position:** '.($this->employee->position?->title ?? 'To be confirmed'))
            ->line('**Department:** '.($this->employee->department?->name ?? 'To be confirmed'))
            ->line('**Sign in with:** '.$notifiable->email)
            ->action('Set your password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire', 60)
                .' minutes. If it lapses, use "Forgotten?" on the sign-in page to request a new one.')
            ->line('Once signed in you can view your record, keep your contact details current, and read company announcements.')
            ->line('If anything on your record is wrong, please tell Human Resources — some details can only be changed by them.')
            ->replyTo(setting('contact.email_hr', setting('contact.email_general')), setting('company.name'))
            ->salutation('Kind regards,'."\n".'Human Resources'."\n".setting('company.name'));
    }
}
