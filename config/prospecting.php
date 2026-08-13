<?php

return [

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
