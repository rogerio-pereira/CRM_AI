<?php

namespace App\Console\Commands;

use App\Enums\AgentType;
use App\Services\AiOrchestrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('prospecting:run')]
#[Description('Dispatch the automated prospecting agent via AI orchestration')]
class RunProspectingCommand extends Command
{
    public function handle(AiOrchestrationService $orchestration): int
    {
        $enabled = config('prospecting.enabled');

        if ($enabled !== true) {
            $this->info('Prospecting is disabled (PROSPECTING_ENABLED).');

            return self::SUCCESS;
        }

        $payload = [
            'triggered_by' => 'prospecting:run',
            'triggered_at' => now()->toIso8601String(),
        ];

        $orchestration->dispatch(AgentType::Prospecting, $payload);

        $this->info('Prospecting agent job dispatched.');

        return self::SUCCESS;
    }
}
