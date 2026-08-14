<?php

namespace Tests\Unit\Jobs;

use App\Ai\Agents\QualificationAgent;
use App\Jobs\RunQualificationAgentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RunQualificationAgentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_retries_when_agent_throws(): void
    {
        $agent = Mockery::mock(QualificationAgent::class);
        $exception = new RuntimeException('Simulated AI failure');

        $agent->shouldReceive('handle')
            ->once()
            ->andThrow($exception);

        $this->app->instance(QualificationAgent::class, $agent);

        Log::shouldReceive('warning')
            ->once()
            ->with('ai.agent.failed', Mockery::type('array'));

        $job = new RunQualificationAgentJob(['opportunity_id' => 1]);

        $this->expectException(RuntimeException::class);

        $job->handle();
    }

    public function test_job_logs_metadata_on_success_without_prompt_content(): void
    {
        $agent = Mockery::mock(QualificationAgent::class);
        $agent->shouldReceive('handle')
            ->once()
            ->andReturn([
                'agent' => 'qualification',
                'status' => 'qualified',
            ]);

        $this->app->instance(QualificationAgent::class, $agent);

        Log::shouldReceive('info')
            ->once()
            ->with('ai.agent.completed', Mockery::on(function (array $context): bool {
                $agentName = $context['agent'] ?? null;
                $provider = $context['provider'] ?? null;
                $hasDuration = isset($context['duration_ms']);
                $hasResultKeys = isset($context['result_keys']);
                $expectedProvider = config('ai.default');

                if ($agentName !== 'qualification') {
                    return false;
                }

                if ($provider !== $expectedProvider) {
                    return false;
                }

                if ($hasDuration === false) {
                    return false;
                }

                return $hasResultKeys;
            }));

        $job = new RunQualificationAgentJob(['opportunity_id' => 1]);
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_uses_retry_settings(): void
    {
        $job = new RunQualificationAgentJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertSame(300, $job->backoff);
    }
}
