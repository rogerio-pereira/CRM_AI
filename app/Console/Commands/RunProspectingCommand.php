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
    /**
     * Execute the console command.
     */
    public function handle(AiOrchestrationService $orchestration): int
    {
        $orchestration->dispatch(AgentType::Prospecting, [
            'triggered_by' => 'prospecting:run',
            'triggered_at' => now()->toIso8601String(),
        ]);

        $this->info('Prospecting agent job dispatched.');

        return self::SUCCESS;
    }
}
