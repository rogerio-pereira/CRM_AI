<?php

namespace App\Events;

use App\Models\FollowUp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
 * @calls app/Listeners/QueueCalendarEventForFollowUp
 * @calls app/Listeners/EvaluateSlackRulesForFollowUp
 */
class FollowUpUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public FollowUp $followUp) {}
}
