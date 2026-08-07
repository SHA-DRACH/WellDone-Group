<?php

use App\Services\Assistant\KnowledgeBase;
use App\Services\OtpService;
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
