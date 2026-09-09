<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Models\Opportunity;

class ProposalAssistantAgent implements AiAgent
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $rawOpportunityId = $context['opportunity_id'] ?? null;
        $notes = [];

        if ($rawOpportunityId !== null) {
            $opportunityId = (int) $rawOpportunityId;
            $opportunity = Opportunity::find($opportunityId);

            if ($opportunity !== null) {
                $notes = $opportunity->notesForAiContext();
            }
        }

        return [
            'agent' => 'proposal_assistant',
            'status' => 'stub',
            'summary' => 'Proposal assistant agent stub response (FDR-013).',
            'context_keys' => array_keys($context),
            'opportunity_notes' => $notes,
        ];
    }
}
