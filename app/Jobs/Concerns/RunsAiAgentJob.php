<?php

namespace App\Jobs\Concerns;

use App\Ai\Contracts\AiAgent;
use App\Enums\AgentType;
use Illuminate\Support\Facades\Log;
use Throwable;

trait RunsAiAgentJob
{
    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 300;

    abstract protected function agentType(): AgentType;

    abstract protected function resolveAgent(): AiAgent;

    public function handle(): void
    {
        $agentType = $this->agentType();
        $startedAt = microtime(true);

        try {
            $result = $this->resolveAgent()->handle($this->payload);

            Log::info('ai.agent.completed', [
                'agent' => $agentType->value,
                'provider' => config('ai.default'),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'result_keys' => array_keys($result),
            ]);
        } catch (Throwable $exception) {
            Log::warning('ai.agent.failed', [
                'agent' => $agentType->value,
                'provider' => config('ai.default'),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
