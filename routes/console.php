<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// CMD to start the schedule:work is :: php artisan schedule:work
Schedule::command('report:admin --type=sales --format=csv')->dailyAt('02:00');
Schedule::command('report:admin --type=inventory --format=csv')->dailyAt('08:00');
