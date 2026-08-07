<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an employee what happened to their leave request.
 *
 * A refusal carries the reason. Being told "no" without being told why is the
 * complaint HR ends up handling twice.
 */
class LeaveRequestDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->request->isApproved();

        $message = (new MailMessage)
            ->subject(($approved ? 'Leave approved' : 'Leave not approved').' — '.$this->request->reference)
            ->greeting('Hello '.$this->request->employee->first_name.',')
            ->line($approved
                ? 'Your leave request has been approved.'
                : 'Your leave request has not been approved.')
            ->line('**Type:** '.$this->request->leaveType->name)
            ->line('**Dates:** '.$this->request->period_label)
            ->line('**Working days:** '.rtrim(rtrim(number_format((float) $this->request->days, 2), '0'), '.'))
            ->line('**Reference:** '.$this->request->reference);

        if (filled($this->request->review_notes)) {
            $message->line('**Note from '.($this->request->reviewer?->name ?? 'the reviewer').':** '
                .$this->request->review_notes);
        }

        if (! $approved) {
            $message->line('The days have been returned to your balance. Speak to your manager or '
                .'Human Resources if you would like to discuss it.');
        }

        return $message
            ->action('View in the staff portal', route('portal.leave.show', $this->request))
            ->salutation('Kind regards,'."\n".'Human Resources'."\n".setting('company.name'));
    }
}
