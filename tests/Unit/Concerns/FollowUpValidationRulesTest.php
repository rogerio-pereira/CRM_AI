<?php

namespace Tests\Unit\Concerns;

use App\Concerns\FollowUpValidationRules;
use App\Enums\FollowUpPriority;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Tests\TestCase;

class FollowUpValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_rules_require_client_and_due_at(): void
    {
        $rules = FollowUpValidationRules::formRules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()
                        ->toArray();

        $this->assertArrayHasKey('client_id', $errors);
        $this->assertArrayHasKey('due_at', $errors);
        $this->assertArrayHasKey('priority', $errors);
    }

    public function test_form_rules_accept_valid_payload(): void
    {
        $client = Client::factory()
                        ->create();
        $dueAt = Carbon::now()
                        ->addDay()
                        ->toDateTimeString();
        $rules = FollowUpValidationRules::formRules();

        $validator = Validator::make([
            'client_id' => $client->id,
            'due_at' => $dueAt,
            'priority' => FollowUpPriority::High->value,
            'notes' => 'Follow up notes',
        ], $rules);

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
