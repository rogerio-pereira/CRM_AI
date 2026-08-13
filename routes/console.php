<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('prospecting:run')
    ->weekdays()
    ->at('08:00');
