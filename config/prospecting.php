<?php

$rawEnabled = env('PROSPECTING_ENABLED', false);
$enabled = filter_var($rawEnabled, FILTER_VALIDATE_BOOLEAN);

return [

    /*
    |--------------------------------------------------------------------------
    | Prospecting Enabled
    |--------------------------------------------------------------------------
    |
    | When false, artisan prospecting:run and the weekday schedule do not
    | dispatch the prospecting agent.
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
