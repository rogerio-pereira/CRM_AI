<?php

namespace App\Http\Requests;

use App\Concerns\ClientValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    use ClientValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->clientValidationRules();
    }
}
