<?php

namespace App\Concerns;

use App\Enums\TaskPriority;
use Illuminate\Validation\Rule;

trait TaskValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function formRules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'is_important' => ['boolean'],
        ];
    }
}
