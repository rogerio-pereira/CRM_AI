<?php

namespace App\Services;

use App\Enums\AgentType;
use App\Jobs\RunProposalAssistantAgentJob;
use App\Jobs\RunProspectingAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Jobs\RunRecommendationAgentJob;
use InvalidArgumentException;

/**
 * Central entry point for enqueueing AI agent work (ADR-003).
 *
 * Dispatches responsibility-specific queue jobs; agents use stub handlers until
 * features 10–13 replace them with Laravel AI SDK calls. No provider failover (ADR-002).
 */
class AiOrchestrationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(AgentType $type, array $payload): void
    {
        $jobClass = $this->jobClassFor($type);

        $jobClass::dispatch($payload);
    }

    /**
     * @return class-string
     */
    public function jobClassFor(AgentType $type): string
    {
        if ($type === AgentType::Prospecting) {
            return RunProspectingAgentJob::class;
        }

        if ($type === AgentType::Qualification) {
            return RunQualificationAgentJob::class;
        }

        if ($type === AgentType::Recommendation) {
            return RunRecommendationAgentJob::class;
        }

        if ($type === AgentType::ProposalAssistant) {
            return RunProposalAssistantAgentJob::class;
        }

        throw new InvalidArgumentException('Unknown agent type: '.$type->value);
    }
}
