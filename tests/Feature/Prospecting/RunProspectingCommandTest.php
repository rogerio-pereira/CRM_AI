<?php

namespace Tests\Feature\Prospecting;

use App\Jobs\RunProspectingAgentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunProspectingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_prospecting_agent_job(): void
    {
        Queue::fake();

        $this->artisan('prospecting:run')
            ->assertSuccessful()
            ->expectsOutputToContain('Prospecting agent job dispatched.');

        Queue::assertPushed(RunProspectingAgentJob::class, function (RunProspectingAgentJob $job): bool {
            $triggeredBy = $job->payload['triggered_by'] ?? null;
            $triggeredAt = $job->payload['triggered_at'] ?? null;

            if ($triggeredBy !== 'prospecting:run') {
                return false;
            }

            return $triggeredAt !== null;
        });
    }

    public function test_command_does_not_dispatch_when_prospecting_is_disabled(): void
    {
        Queue::fake();

        config([
            'prospecting.enabled' => false,
        ]);

        $this->artisan('prospecting:run')
            ->assertSuccessful()
            ->expectsOutputToContain('Prospecting is disabled');

        Queue::assertNothingPushed();
    }
}
