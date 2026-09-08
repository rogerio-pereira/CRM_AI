<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('prospecting:run')
    ->weekdays()
    ->at('04:00')   // 8am EST, Laravel cloud is UTC
    ->when(function (): bool {
        return config('prospecting.enabled') === true;
    });
