<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Internal alert to the sales inbox when a new lead arrives. */
class QuoteRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public QuoteRequest $quote) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quote = $this->quote;

        return (new MailMessage)
            ->subject('New quotation request — '.$quote->reference.($quote->company_name ? ' — '.$quote->company_name : ''))
            ->greeting('New quotation request')
            ->line('**Reference:** '.$quote->reference)
            ->line('**Division:** '.($quote->division?->name ?? 'Not specified'))
            ->line('**Services:** '.($quote->services->pluck('name')->implode(', ') ?: 'Not specified'))
            ->line('**Project:** '.$quote->project_title)
            ->line('**Contact:** '.$quote->contact_name.($quote->company_name ? ' — '.$quote->company_name : ''))
            ->line('**Email:** '.$quote->email)
            ->line('**Phone:** '.$quote->phone)
            ->line('**Budget:** '.($quote->budget_range ?: 'Not stated'))
            ->line('**Timeline:** '.($quote->timeline ?: 'Not stated'))
            ->line('**Attachments:** '.$quote->files->count())
            ->line('---')
            ->line(Str::limit($quote->description, 600))
            ->action('Open in the admin panel', route('admin.quotes.show', $quote));
    }
}
