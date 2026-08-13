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
    | Maximum number of lead candidates to persist per prospecting run.
    |
    */

    'default_limit' => (int) env('PROSPECTING_DEFAULT_LIMIT', 20),

];
