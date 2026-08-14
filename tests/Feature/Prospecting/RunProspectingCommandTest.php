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

        config([
            'prospecting.default_limit' => 3,
        ]);

        $this->artisan('prospecting:run')
            ->assertSuccessful()
            ->expectsOutputToContain('Prospecting agent jobs dispatched: 3.');

        Queue::assertPushed(RunProspectingAgentJob::class, 3);
        Queue::assertPushed(RunProspectingAgentJob::class, function (RunProspectingAgentJob $job): bool {
            $triggeredBy = $job->payload['triggered_by'] ?? null;
            $triggeredAt = $job->payload['triggered_at'] ?? null;
            $limit = $job->payload['limit'] ?? null;

            if ($triggeredBy !== 'prospecting:run') {
                return false;
            }

            if ($limit !== 1) {
                return false;
            }

            return $triggeredAt !== null;
        });
    }

    public function test_command_dispatches_even_when_the_schedule_is_disabled(): void
    {
        Queue::fake();

        config([
            'prospecting.enabled' => false,
            'prospecting.default_limit' => 2,
        ]);

        $this->artisan('prospecting:run')
            ->assertSuccessful()
            ->expectsOutputToContain('Prospecting agent jobs dispatched: 2.');

        Queue::assertPushed(RunProspectingAgentJob::class, 2);
    }

    public function test_command_dispatches_at_least_one_job_when_limit_is_invalid(): void
    {
        Queue::fake();

        config([
            'prospecting.default_limit' => 0,
        ]);

        $this->artisan('prospecting:run')
            ->assertSuccessful()
            ->expectsOutputToContain('Prospecting agent jobs dispatched: 1.');

        Queue::assertPushed(RunProspectingAgentJob::class, 1);
    }
}
