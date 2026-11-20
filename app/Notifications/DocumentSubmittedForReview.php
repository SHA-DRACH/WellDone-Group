<?php

namespace App\Notifications;

use App\Models\DocumentReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the CEO / Managing Director that a division has sent a document for
 * review. The document itself is not attached — it is private, and is opened
 * from the staff panel behind sign-in.
 */
class DocumentSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DocumentReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->review->loadMissing(['division:id,name', 'submitter:id,name']);

        $mail = (new MailMessage)
            ->subject('For your review: '.$review->title.' — '.$review->reference)
            ->greeting('Hello '.Str::before((string) $notifiable->name, ' ').',')
            ->line(($review->submitter?->name ?? 'A member of staff').' has sent a document for your review.')
            ->line('**Document:** '.$review->title)
            ->line('**Type:** '.$review->category_label)
            ->line('**Division:** '.($review->division?->name ?? '—'))
            ->line('**Reference:** '.$review->reference);

        if (filled($review->notes)) {
            $mail->line('**Note from the division:** '.Str::limit($review->notes, 400));
        }

        return $mail
            ->action('Review the document', route('admin.reviews.show', $review))
            ->line('You can approve it, reject it, or ask for changes. The division is told your decision by email.')
            ->salutation('Kind regards,'."\n".setting('company.name'));
    }
}
