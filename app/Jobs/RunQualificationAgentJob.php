<?php

namespace App\Jobs;

use App\Ai\Agents\QualificationAgent;
use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\QualificationFailedException;
use App\Enums\AgentType;
use App\Enums\QualificationStatus;
use App\Jobs\Concerns\RunsAiAgentJob;
use App\Models\Opportunity;
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
        $rawOpportunityId = $this->payload['opportunity_id'] ?? null;

        if ($rawOpportunityId === null) {
            return;
        }

        $opportunityId = (int) $rawOpportunityId;
        $opportunity = Opportunity::find($opportunityId);

        if ($opportunity === null) {
            return;
        }

        if ($opportunity->qualification_status === QualificationStatus::Qualified) {
            return;
        }

        $error = 'Qualification could not be completed. The team can try again later.';

        if ($exception instanceof QualificationFailedException) {
            $error = $exception->getMessage();
        }

        $opportunity->update([
            'qualification_status' => QualificationStatus::Failed,
            'qualification_last_error' => $error,
        ]);
    }
}
