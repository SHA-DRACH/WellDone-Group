<?php

namespace App\Notifications;

use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Double opt-in confirmation for a newsletter signup. */
class NewsletterConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Newsletter $subscriber) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Please confirm your newsletter subscription')
            ->greeting($this->subscriber->name ? 'Hello '.$this->subscriber->name.',' : 'Hello,')
            ->line('Please confirm that you would like to receive news and updates from '.setting('company.name').'.')
            ->action('Confirm subscription', route('site.newsletter.confirm', $this->subscriber->token))
            ->line('If you did not request this, you can safely ignore this email — no subscription will be created without your confirmation.');
    }
}
