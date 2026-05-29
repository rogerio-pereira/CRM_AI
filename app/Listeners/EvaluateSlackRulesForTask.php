<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;

class EvaluateSlackRulesForTask
{
    public function handle(TaskCreated|TaskUpdated $event): void
    {
        // TODO(FDR-015): Evaluate Slack notification rules for tasks.
    }
}
