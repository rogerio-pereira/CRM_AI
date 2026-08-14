<?php

namespace Tests\Feature\Recommendations;

use App\Ai\Agents\RecommendationAgent;
use App\Ai\Agents\RecommendationAnalysisAgent;
use App\Ai\Exceptions\RecommendationFailedException;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Responses\AgentResponse;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tests\Support\RecommendationFake;
use Tests\TestCase;

class RecommendationAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_recommendation_persists_schema_version_one_on_the_opportunity(): void
    {
        Mail::fake();
        Notification::fake();

        $client = Client::factory()->create([
            'company_name' => 'GreenSprout Lawn Care',
        ]);
        $opportunity = Opportunity::factory()
                                ->for($client)
                                ->qualificationQualified()
                                ->create([
                                    'qualification_notes' => 'Local service business with a weak website.',
                                ]);

        RecommendationFake::fakeSuccessful((string) $opportunity->id, (string) $client->id);

        $agent = app(RecommendationAgent::class);
        $result = $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $recommendations = $opportunity->ai_recommendations;

        $this->assertSame('completed', $result['status']);
        $this->assertSame($opportunity->id, $result['opportunity_id']);
        $this->assertIsArray($recommendations);
        $this->assertSame(1, $recommendations['schema_version']);
        $this->assertSame('recommendation', $recommendations['source_agent']);
        $this->assertSame(
            'Start with a clearer website, then a simple follow-up conversation.',
            $recommendations['summary'],
        );
        $this->assertSame('Outdated website', $recommendations['pain_points'][0]['title']);
        $this->assertSame(
            'Make the first impression easier to act on',
            $recommendations['opportunities'][0]['title'],
        );
        $this->assertSame(
            'Helping more visitors feel ready to call',
            $recommendations['outreach_strategy']['contact_example']['subject'],
        );
        $this->assertSame(
            'Review the example email before any outreach',
            $recommendations['next_steps'][0]['title'],
        );
        $this->assertTrue($opportunity->hasAiRecommendations());

        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
    }

    public function test_unqualified_opportunity_skips_ai_and_does_not_persist_recommendations(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationPending()
                                ->create();

        $agent = app(RecommendationAgent::class);
        $result = $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();

        $this->assertSame('skipped_not_qualified', $result['status']);
        $this->assertNull($opportunity->ai_recommendations);
    }

    public function test_incomplete_recommendation_output_throws(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationQualified()
                                ->create();

        RecommendationFake::fakeIncomplete();

        $agent = app(RecommendationAgent::class);

        $this->expectException(RecommendationFailedException::class);
        $this->expectExceptionMessage('Recommendation output was incomplete.');

        $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_missing_recommendations_payload_throws(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationQualified()
                                ->create();

        RecommendationAnalysisAgent::fake([
            [
                'schema_version' => 1,
                'agent' => 'recommendation',
                'lead_id' => '1',
                'opportunity_id' => '1',
                'ai_recommendations' => null,
            ],
        ]);

        $agent = app(RecommendationAgent::class);

        $this->expectException(RecommendationFailedException::class);
        $this->expectExceptionMessage('Recommendation output was incomplete.');

        $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_refresh_overwrites_existing_recommendations(): void
    {
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()
                                ->for($client)
                                ->qualificationQualified()
                                ->withAiRecommendations()
                                ->create();

        $updatedPayload = RecommendationFake::successfulPayload((string) $opportunity->id, (string) $client->id);
        $updatedPayload['ai_recommendations']['summary'] = 'Updated recommendation after refresh.';
        $updatedPayload['ai_recommendations']['conversation_strategy']['contact_example']['subject'] = 'Updated subject';

        RecommendationAnalysisAgent::fake([
            $updatedPayload,
        ]);

        $agent = app(RecommendationAgent::class);
        $agent->handle([
            'opportunity_id' => $opportunity->id,
            'trigger' => 'manual_refresh',
        ]);

        $opportunity->refresh();
        $recommendations = $opportunity->ai_recommendations;

        $this->assertSame('Updated recommendation after refresh.', $recommendations['summary']);
        $this->assertSame('Updated subject', $recommendations['outreach_strategy']['contact_example']['subject']);
    }

    public function test_agent_throws_when_prompt_file_is_missing(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationQualified()
                                ->create();

        File::partialMock()
                ->shouldReceive('exists')
                ->andReturn(false);

        $agent = app(RecommendationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Recommendation prompt file not found');

        $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_approved_prompt_forbids_autonomous_outreach(): void
    {
        $promptPath = base_path('docs/prompts/recommendation-agent.md');
        $prompt = File::get($promptPath);
        $promptText = (string) $prompt;

        $this->assertStringContainsString('Do not send emails, DMs, calls, proposals, or client-facing messages', $promptText);
        $this->assertStringContainsString('Recommendations are read-only until a user acts', $promptText);
    }

    public function test_agent_throws_when_prompt_file_is_empty(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationQualified()
                                ->create();

        $file = File::partialMock();
        $file->shouldReceive('exists')
                ->andReturn(true);
        $file->shouldReceive('get')
                ->andReturn('   ');

        $agent = app(RecommendationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Recommendation prompt file is empty');

        $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_agent_throws_when_client_is_missing(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationQualified()
                                ->create();

        Client::addGlobalScope('recommendation-missing-client', function ($query): void {
            $query->whereRaw('0 = 1');
        });

        try {
            $agent = app(RecommendationAgent::class);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Recommendation client not found for opportunity: '.$opportunity->id);

            $agent->handle([
                'opportunity_id' => $opportunity->id,
            ]);
        } finally {
            $modelReflection = new ReflectionClass(Client::class);
            $scopesProperty = $modelReflection->getProperty('globalScopes');
            $scopes = $scopesProperty->getValue();
            unset($scopes[Client::class]['recommendation-missing-client']);
            $scopesProperty->setValue(null, $scopes);
        }
    }

    public function test_agent_throws_when_analysis_response_is_not_structured(): void
    {
        $opportunity = Opportunity::factory()
                                ->qualificationQualified()
                                ->create();
        $unstructuredResponse = Mockery::mock(AgentResponse::class);
        $analysisAgent = Mockery::mock(RecommendationAnalysisAgent::class);
        $analysisAgent->shouldReceive('prompt')
                ->once()
                ->andReturn($unstructuredResponse);

        $this->app->bind(RecommendationAnalysisAgent::class, function () use ($analysisAgent) {
            return $analysisAgent;
        });

        $agent = app(RecommendationAgent::class);

        $this->expectException(RecommendationFailedException::class);
        $this->expectExceptionMessage('Recommendation output was incomplete.');

        $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_persists_defaults_when_optional_fields_are_missing(): void
    {
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()
                                ->for($client)
                                ->qualificationQualified()
                                ->create();
        $payload = RecommendationFake::successfulPayload((string) $opportunity->id, (string) $client->id);
        unset(
            $payload['ai_recommendations']['generated_at'],
            $payload['ai_recommendations']['language'],
            $payload['ai_recommendations']['confidence'],
            $payload['ai_recommendations']['pain_points'],
            $payload['ai_recommendations']['recommended_focus'],
            $payload['ai_recommendations']['next_steps'],
        );

        RecommendationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(RecommendationAgent::class);
        $agent->handle([
            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $recommendations = $opportunity->ai_recommendations;

        $this->assertNotSame('', $recommendations['generated_at']);
        $this->assertSame('en', $recommendations['language']);
        $this->assertSame('medium', $recommendations['confidence']);
        $this->assertSame([], $recommendations['pain_points']);
        $this->assertSame([], $recommendations['opportunities']);
        $this->assertSame([], $recommendations['next_steps']);
        $this->assertIsArray($recommendations['outreach_strategy']);
    }

    public function test_persist_throws_when_recommendations_are_not_an_array(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create();
        $agent = app(RecommendationAgent::class);
        $method = new ReflectionMethod(RecommendationAgent::class, 'persistRecommendations');
        $payload = [
                'ai_recommendations' => null,
            ];

        $this->expectException(RecommendationFailedException::class);
        $this->expectExceptionMessage('Recommendation output was incomplete.');

        $method->invoke($agent, $opportunity, $payload);
    }

    public function test_agent_throws_when_opportunity_payload_cannot_be_encoded(): void
    {
        $client = Client::factory()->create();
        $client->company_name = "\xB1\x31";
        $client->save();

        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->create();
        $agent = app(RecommendationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Recommendation opportunity payload could not be encoded.');

        $agent->handle([
                'opportunity_id' => $opportunity->id,
            ]);
    }
}
