<?php

namespace App\Listeners;

use App\Enums\AgentType;
use App\Events\OpportunityCreated;
use App\Services\AiOrchestrationService;

class DispatchAiOnOpportunityCreated
{
    public function __construct(
        private readonly AiOrchestrationService $orchestration,
    ) {}

    public function handle(OpportunityCreated $event): void
    {
        $this->orchestration->dispatch(AgentType::Qualification, [
            'trigger' => 'opportunity_created',
            'opportunity_id' => $event->opportunity->id,
            'client_id' => $event->opportunity->client_id,
        ]);
    }
}
