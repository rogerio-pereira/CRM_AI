<?php

namespace App\Listeners;

use App\Enums\AgentType;
use App\Enums\PipelineStage;
use App\Events\OpportunityStageChanged;
use App\Services\AiOrchestrationService;

class DispatchAiOnOpportunityStageChanged
{
    public function __construct(
        private readonly AiOrchestrationService $orchestration,
    ) {}

    public function handle(OpportunityStageChanged $event): void
    {
        $agentType = $this->agentTypeForStage($event->toStage);

        if ($agentType === null) {
            return;
        }

        $this->orchestration->dispatch($agentType, [
            'trigger' => 'opportunity_stage_changed',
            'opportunity_id' => $event->opportunity->id,
            'client_id' => $event->opportunity->client_id,
            'from_stage' => $event->fromStage->value,
            'to_stage' => $event->toStage->value,
            'user_id' => $event->userId,
        ]);
    }

    private function agentTypeForStage(PipelineStage $stage): ?AgentType
    {
        if ($stage === PipelineStage::Qualification) {
            return AgentType::Qualification;
        }

        if ($stage === PipelineStage::ProposalGeneration) {
            return AgentType::ProposalAssistant;
        }

        if ($stage === PipelineStage::ProposalAnalysis) {
            return AgentType::Recommendation;
        }

        return null;
    }
}
