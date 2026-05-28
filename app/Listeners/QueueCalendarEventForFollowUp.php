<?php

namespace App\Listeners;

use App\Events\FollowUpCreated;
use App\Events\FollowUpUpdated;

class QueueCalendarEventForFollowUp
{
    public function handle(FollowUpCreated|FollowUpUpdated $event): void
    {
        // TODO(FDR-016): Queue Google Calendar event creation for follow-ups.
    }
}
