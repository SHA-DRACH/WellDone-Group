<?php

use Illuminate\Foundation\Inspiring;
use App\Services\OtpService;
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
