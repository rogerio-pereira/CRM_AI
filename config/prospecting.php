<?php

$rawEnabled = env('PROSPECTING_ENABLED', false);
$enabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN);

return [

    /*
    |--------------------------------------------------------------------------
    | Prospecting Enabled
    |--------------------------------------------------------------------------
    |
    | When false, the weekday 08:00 schedule does not run prospecting:run.
    | Manual `php artisan prospecting:run` always dispatches.
    |
    */

    'enabled' => $enabled,

    /*
    |--------------------------------------------------------------------------
    | Prospecting Default Limit
    |--------------------------------------------------------------------------
    |
    | Number of prospecting jobs dispatched per run. Each job discovers and
    | persists one lead so a single queue worker does not time out.
    |
    */

    'default_limit' => (int) env('PROSPECTING_DEFAULT_LIMIT', 20),

];
