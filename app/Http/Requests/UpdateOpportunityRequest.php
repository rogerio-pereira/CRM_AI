<?php

namespace App\Http\Requests;

use App\Concerns\OpportunityValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityRequest extends FormRequest
{
    use OpportunityValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->opportunityValidationRules();
    }
}
