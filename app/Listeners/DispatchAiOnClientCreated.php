<?php

namespace App\Listeners;

use App\Enums\AgentType;
use App\Events\ClientCreated;
use App\Services\AiOrchestrationService;

class DispatchAiOnClientCreated
{
    public function __construct(
        private readonly AiOrchestrationService $orchestration,
    ) {}

    public function handle(ClientCreated $event): void
    {
        // TODO(FDR-011): Expand payload when automated lead qualification ships.
        // No prospecting discovery here (ADR-015).
        $this->orchestration->dispatch(AgentType::Qualification, [
            'trigger' => 'client_created',
            'client_id' => $event->client->id,
        ]);
    }
}
