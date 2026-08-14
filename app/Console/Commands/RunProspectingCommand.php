<?php

namespace App\Console\Commands;

use App\Enums\AgentType;
use App\Services\AiOrchestrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('prospecting:run')]
#[Description('Dispatch one prospecting job per lead via AI orchestration')]
class RunProspectingCommand extends Command
{
    public function handle(AiOrchestrationService $orchestration): int
    {
        $jobCount = (int) config('prospecting.default_limit', 20);

        if ($jobCount < 1) {
            $jobCount = 1;
        }

        $triggeredAt = now()->toIso8601String();

        for ($index = 0; $index < $jobCount; $index++) {
            $payload = [
                'triggered_by' => 'prospecting:run',
                'triggered_at' => $triggeredAt,
                'limit' => 1,
            ];

            $orchestration->dispatch(AgentType::Prospecting, $payload);
        }

        $this->info('Prospecting agent jobs dispatched: '.$jobCount.'.');

        return self::SUCCESS;
    }
}
