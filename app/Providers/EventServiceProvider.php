<?php

namespace App\Providers;

use App\Events\FollowUpCreated;
use App\Events\FollowUpUpdated;
use App\Events\OpportunityStageChanged;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Listeners\EvaluateSlackRulesForFollowUp;
use App\Listeners\EvaluateSlackRulesForTask;
use App\Listeners\QueueCalendarEventForFollowUp;
use App\Listeners\QueueCalendarEventForTask;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        OpportunityStageChanged::class => [],
        FollowUpCreated::class => [
            QueueCalendarEventForFollowUp::class,
            EvaluateSlackRulesForFollowUp::class,
        ],
        FollowUpUpdated::class => [
            QueueCalendarEventForFollowUp::class,
            EvaluateSlackRulesForFollowUp::class,
        ],
        TaskCreated::class => [
            QueueCalendarEventForTask::class,
            EvaluateSlackRulesForTask::class,
        ],
        TaskUpdated::class => [
            QueueCalendarEventForTask::class,
            EvaluateSlackRulesForTask::class,
        ],
    ];
}
