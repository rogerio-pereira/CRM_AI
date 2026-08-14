<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
 * @calls app/Listeners/QueueCalendarEventForTask
 * @calls app/Listeners/EvaluateSlackRulesForTask
 */
class TaskCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Task $task) {}
}
