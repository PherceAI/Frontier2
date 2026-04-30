<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('frontier:sync-legacy-room-occupancy')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('frontier:sync-google-inventory')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('frontier:sync-kitchen-inventory-movements')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('frontier:create-kitchen-inventory-closing')
    ->dailyAt('01:00')
    ->timezone('America/Guayaquil')
    ->withoutOverlapping();
