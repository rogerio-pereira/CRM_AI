<?php

namespace Tests\Feature\Platform;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    public function test_schedule_lists_prospecting_run_command(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('prospecting:run');
    }

    public function test_scheduler_run_succeeds(): void
    {
        $this->artisan('schedule:run')
            ->assertSuccessful();
    }

    public function test_prospecting_is_scheduled_weekdays_at_eight(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $prospectingEvents = collect($events)->filter(function ($event): bool {
            return str_contains($event->command ?? '', 'prospecting:run')
                || str_contains($event->description ?? '', 'prospecting:run');
        });

        $this->assertTrue(
            $prospectingEvents->isNotEmpty(),
            'Expected prospecting:run to be registered on the schedule.',
        );

        $event = $prospectingEvents->first();

        $this->assertSame('0 8 * * 1-5', $event->expression);

        Carbon::setTestNow(Carbon::parse('2026-08-10 08:00:00', config('app.timezone')));
        $this->assertTrue($event->isDue($this->app));

        Carbon::setTestNow(Carbon::parse('2026-08-08 08:00:00', config('app.timezone')));
        $this->assertFalse($event->isDue($this->app));

        Carbon::setTestNow();
    }
}
