<?php

namespace App\Concerns;

trait OpportunityNoteValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function formRules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
