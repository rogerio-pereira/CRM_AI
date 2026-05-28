<?php

namespace Tests\Feature\Platform;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueJobTest extends TestCase
{
    public function test_jobs_can_be_dispatched_on_redis_connection(): void
    {
        Queue::fake();

        $job = new class implements ShouldQueue
        {
            use Queueable;
        };

        dispatch($job)->onConnection('redis');

        Queue::assertPushed($job::class, function (object $pushed): bool {
            return $pushed->connection === 'redis';
        });
    }
}
