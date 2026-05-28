<?php

namespace App\Concerns;

use App\Enums\FollowUpPriority;
use Illuminate\Validation\Rule;

trait FollowUpValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function formRules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'due_at' => ['required', 'date'],
            'priority' => ['required', Rule::enum(FollowUpPriority::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function opportunityBelongsToClientMessages(): array
    {
        return [
            'opportunity_id' => __('The selected opportunity must belong to the same client.'),
        ];
    }
}
