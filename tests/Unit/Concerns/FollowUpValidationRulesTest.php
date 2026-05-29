<?php

namespace Tests\Unit\Concerns;

use App\Concerns\FollowUpValidationRules;
use App\Enums\FollowUpPriority;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Tests\TestCase;

class FollowUpValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_rules_require_client_and_due_at(): void
    {
        $validator = Validator::make([], FollowUpValidationRules::formRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('client_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('due_at', $validator->errors()->toArray());
        $this->assertArrayHasKey('priority', $validator->errors()->toArray());
    }

    public function test_form_rules_accept_valid_payload(): void
    {
        $client = Client::factory()->create();

        $validator = Validator::make([
            'client_id' => $client->id,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => FollowUpPriority::High->value,
            'notes' => 'Follow up notes',
        ], FollowUpValidationRules::formRules());

        $this->assertFalse($validator->fails());
    }

    public function test_form_rules_priority_uses_enum_rule(): void
    {
        $rules = FollowUpValidationRules::formRules();

        $this->assertInstanceOf(Enum::class, $rules['priority'][1]);
    }

    public function test_opportunity_belongs_to_client_messages_returns_expected_key(): void
    {
        $messages = FollowUpValidationRules::opportunityBelongsToClientMessages();

        $this->assertArrayHasKey('opportunity_id', $messages);
        $this->assertNotSame('', $messages['opportunity_id']);
    }
}
