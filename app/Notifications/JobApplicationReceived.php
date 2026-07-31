<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

/**
 * Internal alert to HR. The résumé is deliberately not attached — it stays
 * on the private disk and is reached through the authorised admin route.
 */
class JobApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->application;

        $mail = (new MailMessage)
            ->subject('New application — '.($application->career?->title ?? 'General').' — '.$application->full_name)
            ->greeting('New job application')
            ->line('**Position:** '.($application->career?->title ?? 'General application'))
            ->line('**Reference:** '.$application->reference)
            ->line('**Candidate:** '.$application->full_name)
            ->line('**Email:** '.$application->email)
            ->line('**Phone:** '.$application->phone)
            ->line('**Experience:** '.($application->years_experience !== null ? $application->years_experience.' years' : 'Not stated'))
            ->line('**Qualification:** '.($application->highest_qualification ?: 'Not stated'))
            ->line('**Available from:** '.($application->available_from?->format('j M Y') ?: 'Not stated'))
            ->line('The résumé is available from the application record in the admin panel.');

        // Guarded so the notification can never fail on a missing route.
        if (Route::has('admin.applications.show')) {
            $mail->action('Review the application', route('admin.applications.show', $application));
        }

        return $mail;
    }
}
