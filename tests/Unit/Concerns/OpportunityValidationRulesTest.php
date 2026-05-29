<?php

namespace Tests\Unit\Concerns;

use App\Concerns\OpportunityValidationRules;
use App\Enums\PipelineStage;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Tests\TestCase;

class OpportunityValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_rules_require_title_and_client(): void
    {
        $validator = Validator::make([], OpportunityValidationRules::formRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('client_id', $validator->errors()->toArray());
    }

    public function test_form_rules_accept_valid_payload(): void
    {
        $client = Client::factory()->create();

        $validator = Validator::make([
            'title' => 'Enterprise deal',
            'client_id' => $client->id,
            'estimated_value' => '12000.50',
        ], OpportunityValidationRules::formRules());

        $this->assertFalse($validator->fails());
    }

    public function test_form_rules_reject_negative_estimated_value(): void
    {
        $client = Client::factory()->create();

        $validator = Validator::make([
            'title' => 'Enterprise deal',
            'client_id' => $client->id,
            'estimated_value' => '-1',
        ], OpportunityValidationRules::formRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('estimated_value', $validator->errors()->toArray());
    }

    public function test_stage_rules_require_valid_pipeline_stage(): void
    {
        $validator = Validator::make(['stage' => 'invalid'], OpportunityValidationRules::stageRules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('stage', $validator->errors()->toArray());
    }

    public function test_stage_rules_accept_enum_values(): void
    {
        $rules = OpportunityValidationRules::stageRules();

        $this->assertArrayHasKey('stage', $rules);
        $this->assertInstanceOf(Enum::class, $rules['stage'][1]);

        $validator = Validator::make([
            'stage' => PipelineStage::Contact->value,
        ], $rules);

        $this->assertFalse($validator->fails());
    }
}
