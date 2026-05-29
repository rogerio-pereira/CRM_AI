<?php

namespace App\Jobs;

use App\Ai\Agents\QualificationAgent;
use App\Ai\Contracts\AiAgent;
use App\Enums\AgentType;
use App\Jobs\Concerns\RunsAiAgentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class RunQualificationAgentJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use RunsAiAgentJob;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {
        $this->configureAiAgentJobQueue();
    }

    protected function agentType(): AgentType
    {
        return AgentType::Qualification;
    }

    protected function resolveAgent(): AiAgent
    {
        return app(QualificationAgent::class);
    }
}
