<?php

namespace App\Jobs;

use App\Ai\Agents\QualificationAgent;
use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\QualificationFailedException;
use App\Enums\AgentType;
use App\Enums\QualificationStatus;
use App\Jobs\Concerns\RunsAiAgentJob;
use App\Models\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

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
    ) {}

    protected function agentType(): AgentType
    {
        return AgentType::Qualification;
    }

    protected function resolveAgent(): AiAgent
    {
        return app(QualificationAgent::class);
    }

    public function failed(Throwable $exception): void
    {
        $rawClientId = $this->payload['client_id'] ?? null;

        if ($rawClientId === null) {
            return;
        }

        $clientId = (int) $rawClientId;
        $client = Client::find($clientId);

        if ($client === null) {
            return;
        }

        if ($client->qualification_status === QualificationStatus::Qualified) {
            return;
        }

        $error = 'Qualification could not be completed. The team can try again later.';

        if ($exception instanceof QualificationFailedException) {
            $error = $exception->getMessage();
        }

        $client->update([
            'qualification_status' => QualificationStatus::Failed,
            'qualification_last_error' => $error,
        ]);
    }
}
