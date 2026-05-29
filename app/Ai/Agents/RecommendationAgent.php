<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;

class RecommendationAgent implements AiAgent
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        return [
            'agent' => 'recommendation',
            'status' => 'stub',
            'summary' => 'Recommendation agent stub response (FDR-012).',
            'context_keys' => array_keys($context),
        ];
    }
}
