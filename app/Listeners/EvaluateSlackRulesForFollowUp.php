<?php

namespace App\Listeners;

use App\Events\FollowUpCreated;
use App\Events\FollowUpUpdated;

/*
 * Called by app/Events/FollowUpCreated.php
 * Called by app/Events/FollowUpUpdated.php
 */
class EvaluateSlackRulesForFollowUp
{
    public function handle(FollowUpCreated|FollowUpUpdated $event): void
    {
        // TODO(FDR-015): Evaluate Slack notification rules for follow-ups.
    }
}
