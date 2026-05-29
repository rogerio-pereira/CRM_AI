<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;

class ProposalAssistantAgent implements AiAgent
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        return [
            'agent' => 'proposal_assistant',
            'status' => 'stub',
            'summary' => 'Proposal assistant agent stub response (FDR-013).',
            'context_keys' => array_keys($context),
        ];
    }
}
