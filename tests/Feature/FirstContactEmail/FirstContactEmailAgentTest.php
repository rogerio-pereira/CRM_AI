<?php

namespace Tests\Feature\FirstContactEmail;

use App\Ai\Agents\FirstContactEmailAgent;
use App\Ai\Agents\WriteFirstContactEmailAgent;
use App\Ai\Exceptions\FirstContactEmailFailedException;
use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Responses\AgentResponse;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Tests\Support\QualificationFake;
use Tests\Support\RecommendationFake;
use Tests\TestCase;

class FirstContactEmailAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_writes_email_and_moves_to_contact(): void
    {
        $client = Client::factory()
                        ->create([
                            'contact_name' => 'Sarah',
                            'company_name' => 'GreenSprout Lawn Care',
                        ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);
        $copywriter = QualificationFake::copywriterPayload();

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $insights = $opportunity->ai_insights;

        $this->assertSame('completed', $result['status']);
        $this->assertSame($copywriter['subject'], $insights['outreach_strategy']['contact_example']['subject']);
        $this->assertSame($copywriter['body'], $insights['outreach_strategy']['contact_example']['body']);
        $this->assertSame(PipelineStage::Contact, $opportunity->stage);
        WriteFirstContactEmailAgent::assertPrompted(function ($prompt) use ($client): bool {
            $promptText = $prompt->prompt;
            $hasCompany = str_contains($promptText, $client->company_name);
            $hasInsights = str_contains($promptText, 'ai_insights');
            $hasSummary = str_contains($promptText, 'A local service business that could use a steadier flow of leads.');
            $hasHook = str_contains($promptText, 'The public site looks dated and the next step is hard to find.');

            if ($hasCompany === false) {
                return false;
            }

            if ($hasInsights === false) {
                return false;
            }

            if ($hasSummary === false) {
                return false;
            }

            return $hasHook;
        });
        WriteFirstContactEmailAgent::assertNotPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;

            return str_contains($promptText, 'A simple way to bring in more local conversations');
        });
    }

    public function test_skips_when_opportunity_is_not_qualified(): void
    {
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                            ]);

        $agent = app(FirstContactEmailAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();

        $this->assertSame('skipped_not_qualified', $result['status']);
        $this->assertSame(PipelineStage::Qualification, $opportunity->stage);
    }

    public function test_does_not_move_opportunity_already_past_qualification(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::ProposalGeneration,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();

        $this->assertSame(PipelineStage::ProposalGeneration, $opportunity->stage);
    }

    public function test_empty_copywriter_email_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);

        QualificationFake::fakeCopywriter([
            'channel' => 'email',
            'subject' => '',
            'body' => '',
        ]);

        $agent = app(FirstContactEmailAgent::class);

        $this->expectException(FirstContactEmailFailedException::class);
        $this->expectExceptionMessage('First contact email output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_copywriter_brief_uses_the_highest_priority_opportunity(): void
    {
        $client = Client::factory()
                        ->create([
                            'contact_name' => 'Daniel',
                            'company_name' => 'Lakeland Lawn Co',
                        ]);
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $insights['opportunities'] = [
                [
                    'service' => 'website_design_development',
                    'title' => 'Rebuild the public site',
                    'why_it_matters' => 'A custom site is not the opening for this owner.',
                    'priority' => 'low',
                ],
                [
                    'service' => 'lead_generation',
                    'title' => 'Create a steadier local lead flow',
                    'why_it_matters' => 'Less dependence on referrals for new work.',
                    'priority' => 'high',
                ],
            ];
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => $insights,
                            ]);

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        WriteFirstContactEmailAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;
            $hasLeadGeneration = str_contains($promptText, 'lead_generation');
            $hasReferralGap = str_contains($promptText, 'Less dependence on referrals for new work.');
            $hasWebsiteRebuild = str_contains($promptText, 'A custom site is not the opening for this owner.');

            if ($hasLeadGeneration === false) {
                return false;
            }

            if ($hasReferralGap === false) {
                return false;
            }

            return $hasWebsiteRebuild;
        });
    }

    public function test_copywriter_brief_prefers_recommendation_opportunities(): void
    {
        $client = Client::factory()
                        ->create([
                            'contact_name' => 'Daniel',
                            'company_name' => 'Lakeland Lawn Co',
                        ]);
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $insights['opportunities'] = [
                [
                    'service' => 'website_design_development',
                    'title' => 'Rebuild the public site',
                    'why_it_matters' => 'A custom site is not the opening for this owner.',
                    'priority' => 'high',
                ],
            ];
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => $insights,
                                'ai_recommendations' => [
                                    'summary' => 'Start with a steadier flow of local quote requests.',
                                    'pain_points' => [
                                        [
                                            'title' => 'Referral-only growth',
                                            'evidence' => 'New work still arrives mostly from neighbors.',
                                            'business_impact' => 'Slow weeks leave the crew waiting.',
                                        ],
                                    ],
                                    'opportunities' => [
                                        [
                                            'service' => 'lead_generation',
                                            'title' => 'Create a steadier local lead flow',
                                            'why_it_matters' => 'Less dependence on referrals for new work.',
                                            'priority' => 'high',
                                        ],
                                    ],
                                ],
                            ]);

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        WriteFirstContactEmailAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;
            $hasLeadGeneration = str_contains($promptText, 'lead_generation');
            $hasReferralGap = str_contains($promptText, 'Less dependence on referrals for new work.');
            $hasRecommendationSummary = str_contains($promptText, 'Start with a steadier flow of local quote requests.');
            $hasRecommendations = str_contains($promptText, 'ai_recommendations');
            $hasWebsiteRebuild = str_contains($promptText, 'A custom site is not the opening for this owner.');

            if ($hasLeadGeneration === false) {
                return false;
            }

            if ($hasReferralGap === false) {
                return false;
            }

            if ($hasRecommendationSummary === false) {
                return false;
            }

            if ($hasRecommendations === false) {
                return false;
            }

            return $hasWebsiteRebuild;
        });
    }

    public function test_copywriter_brief_falls_back_when_outreach_and_opportunities_are_not_arrays(): void
    {
        $client = Client::factory()
                        ->create([
                            'contact_name' => 'Maya',
                            'company_name' => 'Plant City Pools',
                        ]);
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $insights['opportunities'] = 'not-an-array';
        $insights['outreach_strategy'] = 'not-an-array';
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => $insights,
                            ]);
        $copywriter = QualificationFake::copywriterPayload();

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $storedInsights = $opportunity->ai_insights;

        $this->assertSame($copywriter['subject'], $storedInsights['outreach_strategy']['contact_example']['subject']);
        $this->assertSame($copywriter['body'], $storedInsights['outreach_strategy']['contact_example']['body']);
        $this->assertSame(PipelineStage::Contact, $opportunity->stage);
        WriteFirstContactEmailAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;
            $hasCompany = str_contains($promptText, 'Plant City Pools');
            $hasInsights = str_contains($promptText, 'ai_insights');

            if ($hasCompany === false) {
                return false;
            }

            return $hasInsights;
        });
    }

    public function test_copywriter_brief_falls_back_when_pain_points_are_malformed(): void
    {
        $client = Client::factory()
                        ->create([
                            'contact_name' => '',
                            'company_name' => 'Solo Lawn Co',
                        ]);
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $insights['pain_points'] = 'not-an-array';
        $insights['opportunities'] = [
                'not-an-array',
            ];
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => $insights,
                            ]);
        $copywriter = QualificationFake::copywriterPayload();

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $storedInsights = $opportunity->ai_insights;

        $this->assertSame($copywriter['subject'], $storedInsights['outreach_strategy']['contact_example']['subject']);
        $this->assertSame(PipelineStage::Contact, $opportunity->stage);
        WriteFirstContactEmailAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;

            return str_contains($promptText, 'Solo Lawn Co');
        });
    }

    public function test_non_json_copywriter_output_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);
        $unstructuredResponse = Mockery::mock(AgentResponse::class);
        $unstructuredResponse->text = 'plain draft without json';
        $copywriter = Mockery::mock(WriteFirstContactEmailAgent::class);
        $copywriter->shouldReceive('prompt')
            ->once()
            ->andReturn($unstructuredResponse);

        $this->app->bind(WriteFirstContactEmailAgent::class, function () use ($copywriter) {
            return $copywriter;
        });

        $agent = app(FirstContactEmailAgent::class);

        $this->expectException(FirstContactEmailFailedException::class);
        $this->expectExceptionMessage('First contact email output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_missing_insights_are_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => null,
                            ]);

        $agent = app(FirstContactEmailAgent::class);

        $this->expectException(FirstContactEmailFailedException::class);
        $this->expectExceptionMessage('First contact email output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_copywriter_dossier_that_cannot_be_encoded_is_incomplete(): void
    {
        $client = Client::factory()
                        ->create();
        $client->company_name = "\xB1\x31";
        $client->save();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);

        $agent = app(FirstContactEmailAgent::class);

        $this->expectException(FirstContactEmailFailedException::class);
        $this->expectExceptionMessage('First contact email output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_agent_throws_when_opportunity_is_missing(): void
    {
        $agent = app(FirstContactEmailAgent::class);

        $this->expectException(ModelNotFoundException::class);

        $agent->handle([
                            'opportunity_id' => 999999,
        ]);
    }

    public function test_agent_throws_when_client_is_missing(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                            ]);

        Client::addGlobalScope('first-contact-missing-client', function ($query): void {
            $query->whereRaw('0 = 1');
        });

        try {
            $agent = app(FirstContactEmailAgent::class);
            $expectedMessage = 'First contact email client not found for opportunity: '.$opportunity->id;

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedMessage);

            $agent->handle([
                            'opportunity_id' => $opportunity->id,
            ]);
        } finally {
            $modelReflection = new ReflectionClass(Client::class);
            $scopesProperty = $modelReflection->getProperty('globalScopes');
            $scopes = $scopesProperty->getValue();
            unset($scopes[Client::class]['first-contact-missing-client']);
            $scopesProperty->setValue(null, $scopes);
        }
    }

    public function test_copywriter_brief_includes_opportunity_notes(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Alex Sales']);
        $client = Client::factory()
                        ->create([
                            'contact_name' => 'Sarah',
                            'company_name' => 'GreenSprout Lawn Care',
                        ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner prefers a simple brochure site first.',
            ]);

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        WriteFirstContactEmailAgent::assertPrompted(function ($prompt) use ($user): bool {
            $promptText = $prompt->prompt;
            $hasNotesKey = str_contains($promptText, 'opportunity_notes');
            $hasNoteBody = str_contains($promptText, 'Owner prefers a simple brochure site first.');
            $hasAuthor = str_contains($promptText, $user->name);

            if ($hasNotesKey === false) {
                return false;
            }

            if ($hasNoteBody === false) {
                return false;
            }

            return $hasAuthor;
        });
    }

    public function test_manual_email_refresh_does_not_move_pipeline_stage(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => QualificationFake::successfulPayload('1', '1')['ai_insights'],
                            ]);

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $copywriter = QualificationFake::copywriterPayload();
        $agent->handle([
                            'trigger' => 'manual_email_refresh',
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $insights = $opportunity->ai_insights;
        $outreachStrategy = $insights['outreach_strategy'];
        $contactExample = $outreachStrategy['contact_example'];

        $this->assertSame(PipelineStage::Qualification, $opportunity->stage);
        $this->assertSame($copywriter['subject'], $contactExample['subject']);
    }

    public function test_clears_previous_examples_before_writing_a_new_email(): void
    {
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $recommendations = RecommendationFake::successfulPayload('1', '1')['ai_recommendations'];
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                                'ai_insights' => $insights,
                                'ai_recommendations' => $recommendations,
                            ]);

        QualificationFake::fakeCopywriter([
            'channel' => 'email',
            'subject' => '',
            'body' => '',
        ]);

        $agent = app(FirstContactEmailAgent::class);

        try {
            $agent->handle([
                                'opportunity_id' => $opportunity->id,
            ]);
            $this->fail('Expected the copywriter to fail after clearing stored examples.');
        } catch (FirstContactEmailFailedException $exception) {
            $this->assertSame('First contact email output was incomplete.', $exception->getMessage());
        }

        $opportunity->refresh();
        $storedInsights = $opportunity->ai_insights;
        $storedRecommendations = $opportunity->ai_recommendations;
        $insightOutreach = $storedInsights['outreach_strategy'];
        $recommendationConversation = $storedRecommendations['conversation_strategy'];

        $this->assertArrayNotHasKey('contact_example', $insightOutreach);
        $this->assertArrayNotHasKey('contact_example', $recommendationConversation);
        WriteFirstContactEmailAgent::assertNotPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;
            $hasOldInsightEmail = str_contains($promptText, 'A simple way to bring in more local conversations');
            $hasOldRecommendationEmail = str_contains($promptText, 'Helping more visitors feel ready to call');

            if ($hasOldInsightEmail) {
                return true;
            }

            return $hasOldRecommendationEmail;
        });
    }

    public function test_manual_email_refresh_writes_from_notes_without_the_previous_example(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Alex Sales']);
        $client = Client::factory()
                        ->create([
                            'contact_name' => 'Sarah',
                            'company_name' => 'GreenSprout Lawn Care',
                        ]);
        $insights = QualificationFake::successfulPayload('1', '1')['ai_insights'];
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Contact,
                                'ai_insights' => $insights,
                            ]);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner prefers a simple brochure site first.',
            ]);

        QualificationFake::fakeCopywriter();

        $agent = app(FirstContactEmailAgent::class);
        $agent->handle([
                            'trigger' => 'manual_email_refresh',
                            'opportunity_id' => $opportunity->id,
        ]);

        WriteFirstContactEmailAgent::assertPrompted(function ($prompt) use ($user): bool {
            $promptText = $prompt->prompt;
            $hasNotesKey = str_contains($promptText, 'opportunity_notes');
            $hasNoteBody = str_contains($promptText, 'Owner prefers a simple brochure site first.');
            $hasAuthor = str_contains($promptText, $user->name);
            $hasOldEmail = str_contains($promptText, 'A simple way to bring in more local conversations');

            if ($hasNotesKey === false) {
                return false;
            }

            if ($hasNoteBody === false) {
                return false;
            }

            if ($hasAuthor === false) {
                return false;
            }

            return $hasOldEmail === false;
        });
    }
}
