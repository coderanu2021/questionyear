<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('practice:generate')->hourlyAt(5)->timezone('Asia/Kolkata')->withoutOverlapping();
Schedule::command('jobs:fetch')->everyThreeHours()->timezone('Asia/Kolkata')->withoutOverlapping();
Schedule::command('jobs:extract')->cron('45 */3 * * *')->timezone('Asia/Kolkata')->withoutOverlapping();
Schedule::command('app:close-expired-job-notifications')->dailyAt('00:15')->timezone('Asia/Kolkata')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
