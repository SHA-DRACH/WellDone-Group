<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmation sent to a candidate after they apply. */
class JobApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your application has been received — '.$this->application->reference)
            ->greeting('Dear '.$this->application->full_name.',')
            ->line('Thank you for applying to '.setting('company.name').'.')
            ->line('**Position:** '.($this->application->career?->title ?? 'General application'))
            ->line('**Your reference:** '.$this->application->reference)
            ->line('Our recruitment team will review your application. If your experience matches what the role requires, we will be in touch to arrange the next step.')
            ->line('Please note that owing to the volume of applications we receive, we are not always able to respond to every candidate individually.')
            ->replyTo(setting('contact.email_hr', setting('contact.email_general', config('mail.from.address'))), setting('company.name'))
            ->salutation('Kind regards,'."\n".'Human Resources'."\n".setting('company.name'));
    }
}
