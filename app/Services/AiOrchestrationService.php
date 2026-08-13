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
 * Dispatches responsibility-specific queue jobs. Prospecting and qualification
 * agents use the Laravel AI SDK; remaining agents stay stubbed until FDR-012/013.
 * No provider failover (ADR-002).
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
        return $this->resolveJobClass($type->value);
    }

    /**
     * @return class-string
     */
    protected function resolveJobClass(string $agentTypeValue): string
    {
        $map = [
            AgentType::Prospecting->value => RunProspectingAgentJob::class,
            AgentType::Qualification->value => RunQualificationAgentJob::class,
            AgentType::Recommendation->value => RunRecommendationAgentJob::class,
            AgentType::ProposalAssistant->value => RunProposalAssistantAgentJob::class,
        ];

        if (! array_key_exists($agentTypeValue, $map)) {
            throw new InvalidArgumentException('Unknown agent type: '.$agentTypeValue);
        }

        return $map[$agentTypeValue];
    }
}
