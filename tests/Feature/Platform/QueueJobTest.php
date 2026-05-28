<?php

namespace Tests\Feature\Platform;

use App\Jobs\TestQueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_queue_job_handle_sets_cache_flag(): void
    {
        Cache::forget('test_queue_job_handled');

        $job = new TestQueueJob;
        $job->handle();

        $this->assertTrue(Cache::get('test_queue_job_handled'));
    }

    public function test_test_queue_job_can_be_dispatched_on_redis_connection(): void
    {
        Queue::fake();

        TestQueueJob::dispatch()->onConnection('redis');

        Queue::assertPushed(TestQueueJob::class, function (TestQueueJob $job): bool {
            return $job->connection === 'redis';
        });
    }
}
