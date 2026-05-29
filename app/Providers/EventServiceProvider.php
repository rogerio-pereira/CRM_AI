<?php

namespace App\Providers;

use App\Events\OpportunityStageChanged;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        OpportunityStageChanged::class => [],
    ];
}
