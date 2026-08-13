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
            return ($job->payload['triggered_by'] ?? null) === 'prospecting:run'
                && isset($job->payload['triggered_at']);
        });
    }
}
