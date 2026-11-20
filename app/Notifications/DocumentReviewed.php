<?php

namespace App\Notifications;

use App\Models\DocumentReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells the person who submitted a document what the reviewer decided.
 */
class DocumentReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DocumentReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->review->loadMissing('reviewer:id,name,position');
        $by = $review->reviewer?->name ?? 'The reviewer';

        $outcome = match ($review->status) {
            'approved' => [$by.' has **approved** your document.', 'success'],
            'rejected' => [$by.' has **rejected** your document.', 'error'],
            default => [$by.' has **asked for changes** to your document. Please make them and submit it again.', null],
        };

        $mail = (new MailMessage)
            ->subject($review->status_label.': '.$review->title.' — '.$review->reference)
            ->greeting('Hello '.Str::before((string) $notifiable->name, ' ').',')
            ->line($outcome[0])
            ->line('**Document:** '.$review->title)
            ->line('**Reference:** '.$review->reference);

        if ($outcome[1]) {
            $mail->level($outcome[1]);
        }

        if (filled($review->review_comment)) {
            $mail->line('**Comment:** '.$review->review_comment);
        }

        return $mail
            ->action('View the document', route('admin.reviews.show', $review))
            ->salutation('Kind regards,'."\n".$by."\n".setting('company.name'));
    }
}
