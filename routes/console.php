<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:database')
    ->dailyAt('00:30')
    ->withoutOverlapping();

Schedule::command('app:sync-sumselprov-news')
    ->cron(config('services.sumselprov.sync_cron', '0 * * * *'))
    ->withoutOverlapping();
