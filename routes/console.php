<?php

use App\Models\EmployeeDocument;
use App\Models\EmployeeTraining;
use App\Models\User;
use App\Notifications\ExpiryDigest;
use App\Services\Assistant\KnowledgeBase;
use App\Services\OtpService;
use App\Support\Roles;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Housekeeping: drop one-time codes older than a week. They are already
 * expired and spent by then; keeping them serves no purpose and the table
 * would grow without bound.
 */
Schedule::call(fn () => app(OtpService::class)->prune())
    ->daily()
    ->name('prune-otp-codes')
    ->withoutOverlapping();

/*
 * Every weekday morning, HR is told what is lapsing: certificates and
 * documents that expired in the last week or expire within 30 days. Sent to
 * the HR Director and HR Managers; nothing is sent on a day with nothing to
 * report.
 */
Artisan::command('hr:expiry-reminders', function () {
    $from = today()->subDays(7)->toDateString();
    $to = today()->addDays(30)->toDateString();

    $items = EmployeeTraining::query()
        ->whereBetween('certificate_expiry', [$from, $to])
        ->whereHas('employee', fn ($q) => $q->employed())
        ->with('employee:id,first_name,middle_name,last_name')
        ->get()
        ->map(fn (EmployeeTraining $t) => ['date' => $t->certificate_expiry, 'employee' => $t->employee->full_name, 'what' => $t->title.' certificate', 'label' => $t->expiry_label])
        ->concat(EmployeeDocument::query()
            ->whereBetween('expiry_date', [$from, $to])
            ->whereHas('employee', fn ($q) => $q->employed())
            ->with('employee:id,first_name,middle_name,last_name')
            ->get()
            ->map(fn (EmployeeDocument $d) => ['date' => $d->expiry_date, 'employee' => $d->employee->full_name, 'what' => $d->type_label, 'label' => $d->expiry_label]))
        ->sortBy('date')
        ->values();

    if ($items->isEmpty()) {
        $this->info('Nothing expiring.');

        return;
    }

    $recipients = User::query()
        ->role([Roles::HR_DIRECTOR, Roles::HR_MANAGER])
        ->where('is_active', true)
        ->get();

    Notification::send($recipients, new ExpiryDigest($items));

    $this->info("Sent {$items->count()} items to {$recipients->count()} people.");
})->purpose('Email HR the certificates and documents that are expiring');

Schedule::command('hr:expiry-reminders')
    ->weekdays()
    ->at('07:30')
    ->name('hr-expiry-reminders')
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The assistant's knowledge base expires on its own timer, so new content
 * appears within the cache window without anyone doing anything. This is for
 * when that wait is not acceptable — a vacancy published minutes before it is
 * announced, a correction to a service description.
 */
Artisan::command('assistant:refresh', function (KnowledgeBase $knowledge) {
    $knowledge->flush();

    $this->info('Rebuilt the site assistant knowledge base: '.count($knowledge->all()).' entries.');
})->purpose('Rebuild the site assistant knowledge base from current site content');
