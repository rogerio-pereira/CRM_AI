<?php

namespace Tests\Feature\Opportunities;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunitySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_persists_extended_fields(): void
    {
        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create([
                                'title' => 'Enterprise rollout',
                                'stage' => PipelineStage::Lead,
                                'estimated_value' => 12500.50,
                                'status' => OpportunityStatus::Open,
                                'proposal_notes' => 'Draft scope pending review.',
                                'proposal_payload' => ['version' => 1],
                                'ai_recommendations' => ['summary' => 'High intent'],
                            ]);

        $this->assertDatabaseHas('opportunities', [
                                'id' => $opportunity->id,
                                'client_id' => $client->id,
                                'title' => 'Enterprise rollout',
                                'stage' => PipelineStage::Lead->value,
                                'estimated_value' => '12500.50',
                                'status' => OpportunityStatus::Open->value,
        ]);

        $fresh = $opportunity->fresh();

        $this->assertSame('12500.50', $fresh->estimated_value);
        $this->assertSame(OpportunityStatus::Open, $fresh->status);
        $this->assertSame(['version' => 1], $fresh->proposal_payload);
        $this->assertSame(['summary' => 'High intent'], $fresh->ai_recommendations);
    }
}
