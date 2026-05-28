<?php

namespace App\Concerns;

use App\Enums\OpportunityStage;
use Illuminate\Validation\Rule;

trait OpportunityValidationRules
{
    /**
     * @return array<string, mixed>
     */
    protected function opportunityValidationRules(): array
    {
        $stageValues = array_map(
            fn (OpportunityStage $stage): string => $stage->value,
            OpportunityStage::ordered(),
        );

        return [
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'string', Rule::in($stageValues)],
        ];
    }
}
