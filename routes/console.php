<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('prospecting:scheduled', function () {
    $this->comment('Prospecting schedule placeholder (FDR-010 / ADR-007). No agents run yet.');
})->purpose('Weekday 08:00 automated prospecting placeholder until FDR-010');

Schedule::command('prospecting:scheduled')
    ->weekdays()
    ->at('08:00')
    ->description('Automated prospecting placeholder (ADR-007)');
