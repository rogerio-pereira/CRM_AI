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
use InvalidArgumentException;
use ReflectionMethod;
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
        $service = $this->service;

        $prospectingJobClass = $service->jobClassFor(AgentType::Prospecting);
        $qualificationJobClass = $service->jobClassFor(AgentType::Qualification);
        $recommendationJobClass = $service->jobClassFor(AgentType::Recommendation);
        $proposalAssistantJobClass = $service->jobClassFor(AgentType::ProposalAssistant);

        $this->assertSame(RunProspectingAgentJob::class, $prospectingJobClass);
        $this->assertSame(RunQualificationAgentJob::class, $qualificationJobClass);
        $this->assertSame(RunRecommendationAgentJob::class, $recommendationJobClass);
        $this->assertSame(RunProposalAssistantAgentJob::class, $proposalAssistantJobClass);
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

    public function test_dispatch_pushes_job_for_each_agent_type(): void
    {
        Queue::fake();

        $this->service->dispatch(AgentType::Prospecting, ['trigger' => 'manual']);
        $this->service->dispatch(AgentType::Recommendation, ['opportunity_id' => 2]);
        $this->service->dispatch(AgentType::ProposalAssistant, ['opportunity_id' => 3]);

        Queue::assertPushed(RunProspectingAgentJob::class);
        Queue::assertPushed(RunRecommendationAgentJob::class);
        Queue::assertPushed(RunProposalAssistantAgentJob::class);
    }

    public function test_resolve_job_class_throws_for_unknown_agent_type_value(): void
    {
        $method = new ReflectionMethod(AiOrchestrationService::class, 'resolveJobClass');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown agent type: unknown_agent');

        $method->invoke($this->service, 'unknown_agent');
    }
}
