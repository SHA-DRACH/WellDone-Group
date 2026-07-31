<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome message for a visitor who registered on the public website.
 *
 * Sent alongside Laravel's verification email — this one explains what the
 * account is for, the verification email carries the confirmation link.
 */
class ClientAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.setting('company.name'))
            ->greeting('Welcome, '.$this->user->name.'.')
            ->line('Thank you for creating an account with '.setting('company.name').'.')
            ->line('Your account lets you:')
            ->line('• Request a written quotation and track its progress')
            ->line('• Apply for vacancies and follow your applications')
            ->line('• Keep a record of every enquiry you send us')
            ->line('**Please confirm your email address** using the separate verification message we have just sent. Until you do, you will not be able to submit a request.')
            ->action('Go to your account', route('account.dashboard'))
            ->line('If you did not create this account, please ignore both emails and nothing further will happen.')
            ->salutation('Kind regards,'."\n".setting('company.name'));
    }
}
