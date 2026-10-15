<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * The morning list of certificates and documents that have lapsed or are
 * about to, sent to HR. One email with everything, not one per item.
 */
class ExpiryDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Collection<int, array{employee: string, what: string, label: string}> $items */
    public function __construct(public Collection $items) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->items->count().' HR '.str('item')->plural($this->items->count()).' expiring or expired')
            ->greeting('Expiry reminders')
            ->line('These certificates and documents have expired or expire within 30 days:');

        foreach ($this->items->take(40) as $item) {
            $mail->line('• **'.$item['employee'].'** — '.$item['what'].' — '.$item['label']);
        }

        if ($this->items->count() > 40) {
            $mail->line('…and '.($this->items->count() - 40).' more.');
        }

        return $mail->action('Open the HR dashboard', route('admin.hr.dashboard'));
    }
}
