<?php

namespace Tests\Feature\Platform;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $prospectingEvents = $this->prospectingScheduledEvents();

        $this->assertTrue(
            $prospectingEvents->isNotEmpty(),
            'Expected prospecting:run to be registered on the schedule.',
        );

        $event = $prospectingEvents->first();
        $timezone = config('app.timezone');

        $this->assertSame('0 8 * * 1-5', $event->expression);

        $weekday = Carbon::parse('2026-08-10 08:00:00', $timezone);

        Carbon::setTestNow($weekday);

        $isDueOnWeekday = $event->isDue($this->app);

        $this->assertTrue($isDueOnWeekday);

        $weekend = Carbon::parse('2026-08-08 08:00:00', $timezone);

        Carbon::setTestNow($weekend);

        $isDueOnWeekend = $event->isDue($this->app);

        $this->assertFalse($isDueOnWeekend);

        Carbon::setTestNow();
    }

    public function test_prospecting_schedule_is_skipped_when_disabled(): void
    {
        $prospectingEvents = $this->prospectingScheduledEvents();

        $event = $prospectingEvents->first();

        $this->assertNotNull($event);

        config([
            'prospecting.enabled' => false,
        ]);

        $disabledPasses = $event->filtersPass($this->app);

        config([
            'prospecting.enabled' => true,
        ]);

        $enabledPasses = $event->filtersPass($this->app);

        $this->assertFalse($disabledPasses);
        $this->assertTrue($enabledPasses);
    }

    /**
     * @return Collection<int, Event>
     */
    private function prospectingScheduledEvents(): Collection
    {
        $schedule = $this->app->make(Schedule::class);
        $events = $schedule->events();
        $scheduledEvents = collect($events);
        $prospectingEvents = $scheduledEvents->filter(function (Event $event): bool {
            $command = $event->command ?? '';
            $description = $event->description ?? '';
            $matchesCommand = str_contains($command, 'prospecting:run');
            $matchesDescription = str_contains($description, 'prospecting:run');

            return $matchesCommand || $matchesDescription;
        });

        return $prospectingEvents;
    }
}
