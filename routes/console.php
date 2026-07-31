<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FDR-001 placeholder for the FDR-010 prospecting scheduler; replace with prospecting:run in feature 10.
Schedule::command('inspire')->daily();
