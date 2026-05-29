<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;

class ProspectingAgent implements AiAgent
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        return [
            'agent' => 'prospecting',
            'status' => 'stub',
            'summary' => 'Prospecting agent stub response (FDR-010).',
            'context_keys' => array_keys($context),
        ];
    }
}
