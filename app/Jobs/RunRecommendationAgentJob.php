<?php

namespace App\Jobs;

use App\Ai\Agents\RecommendationAgent;
use App\Ai\Contracts\AiAgent;
use App\Enums\AgentType;
use App\Jobs\Concerns\RunsAiAgentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class RunRecommendationAgentJob implements ShouldQueue
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
        return AgentType::Recommendation;
    }

    protected function resolveAgent(): AiAgent
    {
        return app(RecommendationAgent::class);
    }
}
