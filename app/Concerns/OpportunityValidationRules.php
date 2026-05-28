<?php

namespace App\Concerns;

use App\Enums\PipelineStage;
use Illuminate\Validation\Rule;

trait OpportunityValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function formRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function stageRules(): array
    {
        return [
            'stage' => ['required', Rule::enum(PipelineStage::class)],
        ];
    }
}
