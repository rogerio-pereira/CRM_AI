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
        $this->orchestration->dispatch(AgentType::Qualification, [
            'trigger' => 'client_created',
            'client_id' => $event->client->id,
        ]);
    }
}
