<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily jimpitan reminder at 7 AM
Schedule::command('reminder:send')->dailyAt('07:00')->timezone('Asia/Jakarta');

// Daily database backup at midnight
Schedule::command('backup:database')->dailyAt('00:00')->timezone('Asia/Jakarta');

// Daily jadwal jaga announcement at 6 PM
Schedule::command('jadwal:announce')->dailyAt('18:00')->timezone('Asia/Jakarta');
