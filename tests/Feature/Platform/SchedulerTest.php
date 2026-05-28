<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;

class SchedulerTest extends TestCase
{
    public function test_schedule_lists_inspire_command(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('inspire');
    }

    public function test_scheduler_run_succeeds(): void
    {
        $this->artisan('schedule:run')
            ->assertSuccessful();
    }
}
