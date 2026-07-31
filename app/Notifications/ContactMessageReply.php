<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A staff reply to a website enquiry, sent to the original enquirer.
 */
class ContactMessageReply extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContactMessage $message,
        public string $repliedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** Route replies by the department the enquiry was filed under. */
    protected function replyToAddress(): string
    {
        return match ($this->message->department) {
            'sales' => setting('contact.email_sales', setting('contact.email_general')),
            'careers' => setting('contact.email_hr', setting('contact.email_general')),
            default => setting('contact.email_general'),
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Re: '.$this->message->subject)
            ->greeting('Dear '.$this->message->name.',')
            ->line('Thank you for contacting '.setting('company.name').'. Our response follows.')
            ->line('---');

        foreach (preg_split('/\r\n|\r|\n/', $this->message->reply_body) as $paragraph) {
            if (trim($paragraph) !== '') {
                $mail->line($paragraph);
            }
        }

        return $mail
            ->line('---')
            ->line('**Your original enquiry:** '.$this->message->subject)
            ->line('Simply reply to this email if you need anything further.')
            // Replies land in the inbox for the division that answered, so the
            // thread continues with the team that knows the enquiry.
            ->replyTo($this->replyToAddress(), setting('company.name'))
            ->salutation($this->repliedBy."\n".setting('company.name'));
    }
}
