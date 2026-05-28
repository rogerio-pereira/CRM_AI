<?php

namespace Tests\Feature\Platform;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueJobTest extends TestCase
{
    public function test_jobs_can_be_dispatched_to_the_queue(): void
    {
        Queue::fake();

        FakeQueueJob::dispatch();

        Queue::assertPushed(FakeQueueJob::class);
    }
}

final class FakeQueueJob implements ShouldQueue
{
    use Queueable;
}
