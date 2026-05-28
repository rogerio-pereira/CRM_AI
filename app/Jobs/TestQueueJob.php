<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class TestQueueJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('test_queue_job_handled', true, 60);
    }
}
