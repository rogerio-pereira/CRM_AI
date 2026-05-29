<?php

namespace Tests\Unit\Services;

use App\Enums\AgentType;
use App\Jobs\RunProposalAssistantAgentJob;
use App\Jobs\RunProspectingAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Jobs\RunRecommendationAgentJob;
use App\Services\AiOrchestrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiOrchestrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AiOrchestrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AiOrchestrationService::class);
    }

    public function test_job_class_for_each_agent_type(): void
    {
        $this->assertSame(RunProspectingAgentJob::class, $this->service->jobClassFor(AgentType::Prospecting));
        $this->assertSame(RunQualificationAgentJob::class, $this->service->jobClassFor(AgentType::Qualification));
        $this->assertSame(RunRecommendationAgentJob::class, $this->service->jobClassFor(AgentType::Recommendation));
        $this->assertSame(RunProposalAssistantAgentJob::class, $this->service->jobClassFor(AgentType::ProposalAssistant));
    }

    public function test_dispatch_pushes_expected_job(): void
    {
        Queue::fake();

        $payload = ['opportunity_id' => 1];

        $this->service->dispatch(AgentType::Qualification, $payload);

        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($payload): bool {
            return $job->payload === $payload;
        });
    }
}
