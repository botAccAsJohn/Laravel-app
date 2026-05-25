<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{Artisan, Schedule};


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// CMD to start the schedule:work is :: php artisan schedule:work
Schedule::command('report:admin --type=sales --format=csv')->dailyAt('02:00');
Schedule::command('report:admin --type=inventory --format=csv')->dailyAt('08:00');
Schedule::command('slack:daily-digest')->dailyAt('09:00');

// Exercise 46.5 — Daily failed-job digest sent to Slack #ops-alerts at 08:00
Schedule::command('jobs:failed-digest')
    ->dailyAt('08:00')
    ->description('Send daily Slack digest of failed jobs from last 24 hours')
    ->emailOutputOnFailure(config('mail.admin_email', ''));
Schedule::command('scout:flush', ['App\Models\Product'])
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->description('Flush product search index');

Schedule::command('scout:import', ['App\Models\Product'])
    ->weekly()
    ->sundays()
    ->at('02:05')   // 5 min after flush
    ->description('Re-import products to search index');
