<?php

namespace Tests\Unit\Jobs;

use App\Ai\Agents\FirstContactEmailAgent;
use App\Jobs\RunFirstContactEmailAgentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RunFirstContactEmailAgentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_retries_when_agent_throws(): void
    {
        $agent = Mockery::mock(FirstContactEmailAgent::class);
        $exception = new RuntimeException('Simulated AI failure');

        $agent->shouldReceive('handle')
            ->once()
            ->andThrow($exception);

        $this->app->instance(FirstContactEmailAgent::class, $agent);

        Log::shouldReceive('warning')
            ->once()
            ->with('ai.agent.failed', Mockery::type('array'));

        $job = new RunFirstContactEmailAgentJob(['opportunity_id' => 1]);

        $this->expectException(RuntimeException::class);

        $job->handle();
    }

    public function test_job_logs_metadata_on_success_without_prompt_content(): void
    {
        $agent = Mockery::mock(FirstContactEmailAgent::class);
        $agent->shouldReceive('handle')
            ->once()
            ->andReturn([
                'agent' => 'first_contact_email',
                'status' => 'completed',
            ]);

        $this->app->instance(FirstContactEmailAgent::class, $agent);

        Log::shouldReceive('info')
            ->once()
            ->with('ai.agent.completed', Mockery::on(function (array $context): bool {
                $expectedProvider = config('ai.default');

                if ($context['agent'] !== 'first_contact_email') {
                    return false;
                }

                if ($context['provider'] !== $expectedProvider) {
                    return false;
                }

                if (! isset($context['duration_ms'])) {
                    return false;
                }

                return isset($context['result_keys']);
            }));

        $job = new RunFirstContactEmailAgentJob(['opportunity_id' => 1]);
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_uses_retry_settings(): void
    {
        $job = new RunFirstContactEmailAgentJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertSame(300, $job->backoff);
    }
}
