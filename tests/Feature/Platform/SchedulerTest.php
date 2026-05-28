<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;

class SchedulerTest extends TestCase
{
    public function test_schedule_lists_prospecting_placeholder(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('prospecting:scheduled');
    }
}
