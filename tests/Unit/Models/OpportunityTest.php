<?php

namespace Tests\Unit\Models;

use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\QualificationFake;
use Tests\Support\RecommendationFake;
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

    public function test_notes_relationship_returns_related_notes(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $note = OpportunityNote::factory()
                    ->for($opportunity)
                    ->create([
                        'body' => 'Called the owner this morning.',
                    ]);
        $notes = $opportunity->notes;

        $this->assertTrue($notes->contains($note));
        $this->assertSame($opportunity->id, $note->opportunity_id);
    }

    public function test_notes_for_ai_context_returns_chronological_payload(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $firstAuthor = User::factory()
                            ->create(['name' => 'First Author']);
        $secondAuthor = User::factory()
                            ->create(['name' => 'Second Author']);
        $olderNote = OpportunityNote::factory()
                            ->for($opportunity)
                            ->for($firstAuthor)
                            ->create([
                                'body' => 'Older note body.',
                                'created_at' => Carbon::now()
                                                    ->subHour(),
                            ]);
        OpportunityNote::factory()
                            ->for($opportunity)
                            ->for($secondAuthor)
                            ->create([
                                'body' => 'Newer note body.',
                                'created_at' => Carbon::now(),
                            ]);

        $opportunity->unsetRelation('notes');
        $payload = $opportunity->notesForAiContext();
        $firstPayload = $payload[0];
        $secondPayload = $payload[1];
        $olderCreatedAt = $olderNote->created_at;

        $this->assertNotNull($olderCreatedAt);
        $this->assertCount(2, $payload);
        $this->assertSame('Older note body.', $firstPayload['body']);
        $this->assertSame('First Author', $firstPayload['author']);
        $this->assertSame(
            $olderCreatedAt->toIso8601String(),
            $firstPayload['created_at'],
        );
        $this->assertSame('Newer note body.', $secondPayload['body']);
        $this->assertSame('Second Author', $secondPayload['author']);
    }

    public function test_notes_for_ai_context_uses_ai_author_when_user_is_missing(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $note = OpportunityNote::factory()
                    ->for($opportunity)
                    ->create([
                        'user_id' => null,
                        'body' => 'Missing company name or valid public email.',
                    ]);

        $opportunity->unsetRelation('notes');
        $payload = $opportunity->notesForAiContext();
        $firstPayload = $payload[0];

        $this->assertCount(1, $payload);
        $this->assertSame($note->body, $firstPayload['body']);
        $this->assertSame('AI', $firstPayload['author']);
    }

    public function test_forget_contact_examples_removes_stored_email_drafts(): void
    {
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $recommendations = RecommendationFake::successfulPayload('1', '1')['ai_recommendations'];
        $opportunity = Opportunity::factory()
                            ->create([
                                'ai_insights' => $insights,
                                'ai_recommendations' => $recommendations,
                            ]);

        $opportunity->forgetContactExamples();

        $clearedInsights = $opportunity->ai_insights;
        $clearedRecommendations = $opportunity->ai_recommendations;
        $insightOutreach = $clearedInsights['outreach_strategy'];
        $recommendationConversation = $clearedRecommendations['conversation_strategy'];

        $this->assertArrayNotHasKey('contact_example', $insightOutreach);
        $this->assertSame('Helpful local growth conversation.', $insightOutreach['positioning']);
        $this->assertArrayNotHasKey('contact_example', $recommendationConversation);
        $this->assertSame('Helpful local growth conversation.', $recommendationConversation['positioning']);
    }

    public function test_forget_contact_examples_leaves_missing_payloads_unchanged(): void
    {
        $opportunity = Opportunity::factory()
                            ->create([
                                'ai_insights' => null,
                                'ai_recommendations' => null,
                            ]);

        $opportunity->forgetContactExamples();

        $this->assertSame([], $opportunity->ai_insights);
        $this->assertSame([], $opportunity->ai_recommendations);
    }

    public function test_forget_generated_ai_outputs_clears_insights_recommendations_and_qualification_notes(): void
    {
        $user = User::factory()
                    ->create();
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $recommendations = RecommendationFake::successfulPayload('1', '1')['ai_recommendations'];
        $opportunity = Opportunity::factory()
                            ->create([
                                'qualification_notes' => 'Old qualification notes.',
                                'ai_insights' => $insights,
                                'ai_recommendations' => $recommendations,
                            ]);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner prefers a simple brochure site first.',
            ]);

        $opportunity->forgetGeneratedAiOutputs();

        $this->assertNull($opportunity->ai_insights);
        $this->assertNull($opportunity->ai_recommendations);
        $this->assertNull($opportunity->qualification_notes);
        $this->assertSame(1, $opportunity->notes()->count());
    }
}
