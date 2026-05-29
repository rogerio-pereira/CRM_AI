<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;

class QueueCalendarEventForTask
{
    public function handle(TaskCreated|TaskUpdated $event): void
    {
        // TODO(FDR-016): Queue Google Calendar events for important tasks.
    }
}
