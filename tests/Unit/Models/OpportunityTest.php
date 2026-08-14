<?php

namespace Tests\Unit\Models;

use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_ai_recommendations_returns_false_when_null_or_empty(): void
    {
        $withoutRecommendations = Opportunity::factory()
                                        ->create([
                                            'ai_recommendations' => null,
                                        ]);

        $emptyRecommendations = Opportunity::factory()
                                    ->create([
                                        'ai_recommendations' => [],
                                    ]);

        $this->assertFalse($withoutRecommendations->hasAiRecommendations());
        $this->assertFalse($emptyRecommendations->hasAiRecommendations());
    }

    public function test_has_ai_recommendations_returns_true_when_payload_is_present(): void
    {
        $opportunity = Opportunity::factory()
                            ->withAiRecommendations()
                            ->create();

        $this->assertTrue($opportunity->hasAiRecommendations());
    }

    public function test_in_stage_scope_filters_opportunities_by_stage(): void
    {
        $client = Client::factory()
                        ->create();

        $leadOpportunity = Opportunity::factory()
                    ->for($client)
                    ->create([
                        'stage' => PipelineStage::Lead,
                    ]);

        Opportunity::factory()
            ->for($client)
            ->create([
                'stage' => PipelineStage::Won,
            ]);

        $leadResults = Opportunity::query()
                              ->inStage(PipelineStage::Lead)
                              ->get();

        $this->assertCount(1, $leadResults);

        $firstLeadResult = $leadResults->first();

        $this->assertTrue($firstLeadResult->is($leadOpportunity));
    }

    public function test_client_relationship_returns_related_client(): void
    {
        $client = Client::factory()
                        ->create(['company_name' => 'Related Client Co']);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create();
        $relatedClient = $opportunity->client;

        $this->assertTrue($relatedClient->is($client));
        $this->assertSame('Related Client Co', $relatedClient->company_name);
    }

    public function test_tasks_relationship_returns_related_tasks(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $client = $opportunity->client;

        $task = Task::factory()
                    ->for($client)
                    ->create([
                        'opportunity_id' => $opportunity->id,
                        'title' => 'Opportunity task',
                    ]);
        $tasks = $opportunity->tasks;

        $this->assertTrue($tasks->contains($task));
        $this->assertSame($opportunity->id, $task->opportunity_id);
    }
}
