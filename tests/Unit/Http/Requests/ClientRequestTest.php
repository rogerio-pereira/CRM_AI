<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ClientRequest;
use Tests\TestCase;

class ClientRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new ClientRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_rules_delegates_to_form_rules(): void
    {
        $request = new ClientRequest;

        $formRules = ClientRequest::formRules();
        $requestRules = $request->rules();

        $this->assertSame($formRules, $requestRules);
    }

    public function test_form_rules_include_required_company_name(): void
    {
        $rules = ClientRequest::formRules();

        $this->assertArrayHasKey('company_name', $rules);
        $this->assertContains('required', $rules['company_name']);
    }
}
