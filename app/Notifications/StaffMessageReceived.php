<?php

namespace App\Notifications;

use App\Models\StaffMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells a member of staff they have a new message in their staff-panel inbox.
 * The message text is quoted; an attached document is not — it is opened from
 * the inbox, behind sign-in.
 */
class StaffMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public StaffMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->message->loadMissing('sender:id,name,position');
        $from = $message->sender?->name ?? setting('company.name');

        $mail = (new MailMessage)
            ->subject('New message from '.$from.': '.$message->subject)
            ->greeting('Hello '.Str::before((string) $notifiable->name, ' ').',')
            ->line('You have a new message from **'.$from.'**'.($message->sender?->position ? ', '.$message->sender->position : '').'.')
            ->line('**Subject:** '.$message->subject)
            ->line('> '.Str::limit(preg_replace('/\s+/', ' ', $message->body), 500));

        if ($message->hasAttachment()) {
            $mail->line('A document is attached: **'.$message->original_name.'**. Open the message to view it.');
        }

        return $mail
            ->action('Open the message', route('admin.inbox.show', $message))
            ->salutation('Kind regards,'."\n".setting('company.name'));
    }
}
