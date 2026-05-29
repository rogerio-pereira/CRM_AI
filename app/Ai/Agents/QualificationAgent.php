<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;

class QualificationAgent implements AiAgent
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        return [
            'agent' => 'qualification',
            'status' => 'stub',
            'summary' => 'Qualification agent stub response (FDR-011).',
            'context_keys' => array_keys($context),
        ];
    }
}
