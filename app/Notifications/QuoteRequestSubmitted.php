<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmation sent to the person who submitted a quotation request. */
class QuoteRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public QuoteRequest $quote) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $services = $this->quote->services->pluck('name')->all();

        $mail = (new MailMessage)
            ->subject('We have received your quotation request — '.$this->quote->reference)
            ->greeting('Dear '.$this->quote->contact_name.',')
            ->line('Thank you for your enquiry. We have received your request for a quotation and it is now with the relevant division.')
            ->line('**Your reference:** '.$this->quote->reference)
            ->line('**Project:** '.$this->quote->project_title);

        if ($services !== []) {
            $mail->line('**Services requested:** '.implode(', ', $services));
        }

        return $mail
            ->line('A member of our team will review the scope and respond with a written quotation. Please quote your reference number in any correspondence.')
            // Sent from the no-reply address, so point replies at a monitored inbox.
            ->replyTo(setting('contact.email_sales', setting('contact.email_general')), setting('company.name'))
            ->salutation('Kind regards,'."\n".setting('company.name'));
    }
}
