<?php

namespace Tests\Unit\Jobs;

use App\Ai\Agents\RecommendationAgent;
use App\Jobs\RunRecommendationAgentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RunRecommendationAgentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_retries_when_agent_throws(): void
    {
        $agent = Mockery::mock(RecommendationAgent::class);
        $agent->shouldReceive('handle')
            ->once()
            ->andThrow(new RuntimeException('Simulated AI failure'));

        $this->app->instance(RecommendationAgent::class, $agent);

        Log::shouldReceive('warning')
            ->once()
            ->with('ai.agent.failed', Mockery::type('array'));

        $job = new RunRecommendationAgentJob(['opportunity_id' => 1]);

        $this->expectException(RuntimeException::class);

        $job->handle();
    }

    public function test_job_logs_metadata_on_success_without_prompt_content(): void
    {
        $agent = Mockery::mock(RecommendationAgent::class);
        $agent->shouldReceive('handle')
            ->once()
            ->andReturn([
                'agent' => 'recommendation',
                'status' => 'completed',
            ]);

        $this->app->instance(RecommendationAgent::class, $agent);

        Log::shouldReceive('info')
            ->once()
            ->with('ai.agent.completed', Mockery::on(function (array $context): bool {
                return $context['agent'] === 'recommendation'
                    && $context['provider'] === config('ai.default')
                    && isset($context['duration_ms'])
                    && isset($context['result_keys']);
            }));

        $job = new RunRecommendationAgentJob(['opportunity_id' => 1]);
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_uses_retry_settings(): void
    {
        $job = new RunRecommendationAgentJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertSame(300, $job->backoff);
    }
}
