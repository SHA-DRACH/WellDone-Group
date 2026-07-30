<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

/** Internal alert when a contact-form enquiry arrives. */
class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->message;

        $mail = (new MailMessage)
            ->subject('New enquiry — '.$message->subject)
            ->greeting('New website enquiry')
            ->line('**From:** '.$message->name.($message->company ? ' — '.$message->company : ''))
            ->line('**Email:** '.$message->email)
            ->line('**Phone:** '.($message->phone ?: 'Not supplied'))
            ->line('**Department:** '.($message->department ?: 'General'))
            ->line('**Subject:** '.$message->subject)
            ->line('---')
            ->line($message->message)
            // Replying to the alert reaches the enquirer directly.
            ->replyTo($message->email, $message->name);

        // The messages module is being rolled out; only link to it once the
        // route exists, so the notification never fails on a missing route.
        if (Route::has('admin.messages.show')) {
            $mail->action('Open in the admin panel', route('admin.messages.show', $message));
        }

        return $mail;
    }
}
