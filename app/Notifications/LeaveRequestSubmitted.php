<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells whoever can approve it that a request is waiting.
 *
 * Sent to the employee's reporting manager when they have a login, and
 * otherwise to HR — a request nobody is told about is a request nobody
 * answers.
 */
class LeaveRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->request->employee;

        return (new MailMessage)
            ->subject('Leave request awaiting your decision — '.$employee->full_name)
            ->greeting('Hello,')
            ->line($employee->full_name.' has applied for leave.')
            ->line('**Type:** '.$this->request->leaveType->name)
            ->line('**Dates:** '.$this->request->period_label)
            ->line('**Working days:** '.rtrim(rtrim(number_format((float) $this->request->days, 2), '0'), '.'))
            ->when(filled($this->request->reason), fn (MailMessage $m) => $m
                ->line('**Reason given:** '.$this->request->reason))
            ->action('Review the request', route('admin.leave.show', $this->request))
            ->line('The days are already held against their balance, so nobody can spend them twice '
                .'while this sits waiting.')
            ->salutation('Regards,'."\n".setting('company.name'));
    }
}
