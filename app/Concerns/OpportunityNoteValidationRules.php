<?php

namespace App\Concerns;

trait OpportunityNoteValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function noteRules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
