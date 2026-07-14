<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Jobs\DetectStaleTracking;
use App\Jobs\SendUpcomingScheduleReminders;
use App\Jobs\CheckExpiringDocuments;

Schedule::job(new CheckExpiringDocuments)->dailyAt('06:00');
Schedule::job(new SendUpcomingScheduleReminders)->dailyAt('08:00');
Schedule::job(new DetectStaleTracking)->everyMinute();
