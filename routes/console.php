<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| On cPanel, add a cron job that runs every minute:
|   * * * * * /usr/bin/php /path/to/artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('queue:work --stop-when-empty')->everyMinute();

// Last Saturday of every month: notify configured recipients that the consecutive absentees report is ready.
Schedule::command('acm:monthly-absentee-alert')
    ->saturdays()
    ->when(fn () => now()->copy()->addDays(7)->month !== now()->month);
