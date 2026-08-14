<?php

namespace Tests\Feature\Qualification;

use App\Ai\Agents\QualificationAgent;
use App\Ai\Agents\QualificationAnalysisAgent;
use App\Ai\Exceptions\QualificationFailedException;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Jobs\RunQualificationAgentJob;
use App\Jobs\RunRecommendationAgentJob;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\ClientService;
use App\Services\OpportunityService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Responses\AgentResponse;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tests\Support\QualificationFake;
use Tests\TestCase;

class QualificationAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_qualification_updates_opportunity_stays_in_qualification_and_dispatches_recommendation(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $client = Client::factory()
                        ->create([
                            'company_name' => 'GreenSprout Lawn Care',
                            'website' => 'https://greensprout.example',
                        ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);
        $sibling = Opportunity::factory()
                        ->for($client)
                        ->create([
                            'title' => 'Sibling stays put',
                            'stage' => PipelineStage::Lead,
                        ]);

        $opportunityId = (string) $opportunity->id;
        $clientId = (string) $client->id;

        QualificationFake::fakeSuccessful($opportunityId, $clientId);

        $agent = app(QualificationAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $sibling->refresh();

        $this->assertSame('qualified', $result['status']);
        $this->assertSame($opportunity->id, $result['opportunity_id']);
        $this->assertSame(QualificationStatus::Qualified, $opportunity->qualification_status);
        $this->assertSame(
            'Local service business with a weak website and referral-heavy growth.',
            $opportunity->qualification_notes,
        );
        $this->assertNotNull($opportunity->qualified_at);
        $this->assertNull($opportunity->qualification_last_error);

        $insights = $opportunity->ai_insights;
        $this->assertIsArray($insights);
        $this->assertSame(1, $insights['schema_version']);
        $this->assertSame('qualification', $insights['source_agent']);
        $this->assertSame(
            'A simple way to bring in more local conversations',
            $insights['outreach_strategy']['contact_example']['subject'],
        );

        $this->assertSame(PipelineStage::Qualification, $opportunity->stage);
        $this->assertSame(PipelineStage::Lead, $sibling->stage);
        $this->assertSame(QualificationStatus::Pending, $sibling->qualification_status);

        Queue::assertPushed(RunRecommendationAgentJob::class, function (RunRecommendationAgentJob $job) use ($opportunity, $client): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;
            $payloadClientId = $job->payload['client_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;

            if ($payloadOpportunityId !== $opportunity->id) {
                return false;
            }

            if ($payloadClientId !== $client->id) {
                return false;
            }

            return $trigger === 'qualification_completed';
        });
    }

    public function test_manual_opportunity_creation_enqueues_qualification(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $client = Client::factory()
                        ->create([
                            'company_name' => 'Manual Qualify Co',
                        ]);
        $opportunityService = app(OpportunityService::class);
        $opportunity = $opportunityService->create([
                            'client_id' => $client->id,
                            'title' => 'Manual deal',
        ]);

        $this->assertSame(QualificationStatus::Pending, $opportunity->qualification_status);

        Queue::assertPushed(RunQualificationAgentJob::class, 1);
        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($opportunity): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;

            if ($payloadOpportunityId !== $opportunity->id) {
                return false;
            }

            return $trigger === 'opportunity_created';
        });
    }

    public function test_client_only_create_does_not_enqueue_qualification(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $clientService = app(ClientService::class);

        $clientService->create([
                            'company_name' => 'No Opportunity Co',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_new_opportunity_on_already_qualified_client_is_analyzed_again(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $client = Client::factory()
                        ->create([
                            'lead_source' => 'prospecting',
                        ]);
        $existing = Opportunity::factory()
                        ->for($client)
                        ->qualificationQualified()
                        ->create([
                            'title' => 'Website now',
                            'stage' => PipelineStage::Contact,
                        ]);
        $newDeal = Opportunity::factory()
                        ->for($client)
                        ->create([
                            'title' => 'Content later',
                            'stage' => PipelineStage::Lead,
                        ]);

        $opportunityId = (string) $newDeal->id;
        $clientId = (string) $client->id;

        QualificationFake::fakeSuccessful($opportunityId, $clientId);

        $agent = app(QualificationAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => $newDeal->id,
        ]);

        $existing->refresh();
        $newDeal->refresh();

        $this->assertSame('qualified', $result['status']);
        $this->assertSame(QualificationStatus::Qualified, $newDeal->qualification_status);
        $this->assertSame(PipelineStage::Qualification, $newDeal->stage);
        $this->assertSame(QualificationStatus::Qualified, $existing->qualification_status);
        $this->assertSame(PipelineStage::Contact, $existing->stage);
        $this->assertSame('Website now', $existing->title);
    }

    public function test_already_qualified_opportunity_skips_ai(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Contact,
                            ]);

        $agent = app(QualificationAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();

        $this->assertSame('already_qualified', $result['status']);
        $this->assertSame(PipelineStage::Contact, $opportunity->stage);
        Queue::assertNothingPushed();
    }

    public function test_prospecting_initial_run_stores_catalog_entries_on_a_single_opportunity(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $client = Client::factory()
                        ->create([
                            'lead_source' => 'prospecting',
                        ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create([
                                'title' => $client->company_name,
                                'stage' => PipelineStage::Lead,
                            ]);

        $catalogOpportunities = $this->catalogOpportunityEntries();
        $expectedCount = count($catalogOpportunities);

        $opportunityId = (string) $opportunity->id;
        $clientId = (string) $client->id;

        QualificationFake::fakeSuccessful($opportunityId, $clientId, $catalogOpportunities);

        $agent = app(QualificationAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();
        $insights = $opportunity->ai_insights;
        $storedOpportunities = $insights['opportunities'] ?? null;

        $this->assertSame(QualificationStatus::Qualified, $opportunity->qualification_status);
        $this->assertIsArray($storedOpportunities);
        $this->assertCount($expectedCount, $storedOpportunities);
        $this->assertGreaterThan(0, $expectedCount);
        $opportunityQuery = Opportunity::query();
        $opportunityQuery->where('client_id', $client->id);
        $clientOpportunities = $opportunityQuery->count('*');

        $this->assertSame(1, $clientOpportunities);
    }

    public function test_failed_ai_status_throws_user_safe_exception(): void
    {
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);

        QualificationFake::fakeFailed('Not enough public information to qualify this lead.');

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Not enough public information to qualify this lead.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_job_failed_callback_stores_user_safe_error_on_the_opportunity(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationProcessing()
                            ->create([
                                'stage' => PipelineStage::Qualification,
                            ]);

        $job = new RunQualificationAgentJob([
                            'opportunity_id' => $opportunity->id,
        ]);
        $exception = new QualificationFailedException('Not enough public information to qualify this lead.');

        $job->failed($exception);

        $opportunity->refresh();

        $this->assertSame(QualificationStatus::Failed, $opportunity->qualification_status);
        $this->assertSame(
            'Not enough public information to qualify this lead.',
            $opportunity->qualification_last_error,
        );
        $this->assertSame(PipelineStage::Qualification, $opportunity->stage);
    }

    public function test_job_failed_callback_uses_generic_error_for_provider_exceptions(): void
    {
        $opportunity = Opportunity::factory()
                            ->qualificationProcessing()
                            ->create();

        $job = new RunQualificationAgentJob([
                            'opportunity_id' => $opportunity->id,
        ]);

        $exception = new RuntimeException('OpenAI 500 internal provider dump');

        $job->failed($exception);

        $opportunity->refresh();

        $this->assertSame(QualificationStatus::Failed, $opportunity->qualification_status);
        $this->assertSame(
            'Qualification could not be completed. The team can try again later.',
            $opportunity->qualification_last_error,
        );
        $this->assertStringNotContainsString('OpenAI', (string) $opportunity->qualification_last_error);
    }

    public function test_missing_contact_example_is_treated_as_incomplete_output(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['ai_insights']['outreach_strategy']['contact_example']['body'] = '';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_missing_outreach_strategy_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['ai_insights']['outreach_strategy'] = null;

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_missing_contact_example_object_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['ai_insights']['outreach_strategy']['contact_example'] = null;

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_empty_contact_subject_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['ai_insights']['outreach_strategy']['contact_example']['subject'] = '  ';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_agent_throws_when_opportunity_id_is_missing(): void
    {
        $agent = app(QualificationAgent::class);

        $this->expectException(ModelNotFoundException::class);

        $agent->handle([]);
    }

    public function test_agent_throws_when_opportunity_is_missing(): void
    {
        $agent = app(QualificationAgent::class);

        $this->expectException(ModelNotFoundException::class);

        $agent->handle([
                            'opportunity_id' => 999999,
        ]);
    }

    public function test_failed_status_without_error_uses_default_message(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $payload = QualificationFake::failedPayload('');
        $payload['qualification_last_error'] = '   ';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('The opportunity could not be qualified from the available information.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_unknown_qualification_status_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['qualification_status'] = 'maybe';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_missing_insights_is_incomplete(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['ai_insights'] = null;

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_empty_notes_are_stored_as_null(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $opportunity = Opportunity::factory()
                            ->create();
        $opportunityId = (string) $opportunity->id;
        $payload = QualificationFake::successfulPayload($opportunityId);
        $payload['qualification_notes'] = '   ';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);
        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);

        $opportunity->refresh();

        $this->assertNull($opportunity->qualification_notes);
        $this->assertSame(QualificationStatus::Qualified, $opportunity->qualification_status);
    }

    public function test_job_failed_callback_ignores_missing_opportunity_and_already_qualified(): void
    {
        $qualified = Opportunity::factory()
                            ->qualificationQualified()
                            ->create();

        $missingOpportunityJob = new RunQualificationAgentJob([]);
        $missingOpportunityException = new RuntimeException('ignored');
        $missingOpportunityJob->failed($missingOpportunityException);

        $unknownOpportunityJob = new RunQualificationAgentJob([
                            'opportunity_id' => 999999,
        ]);
        $unknownOpportunityException = new RuntimeException('ignored');
        $unknownOpportunityJob->failed($unknownOpportunityException);

        $qualifiedJob = new RunQualificationAgentJob([
                            'opportunity_id' => $qualified->id,
        ]);
        $qualifiedException = new RuntimeException('should not overwrite');
        $qualifiedJob->failed($qualifiedException);

        $qualified->refresh();

        $this->assertSame(QualificationStatus::Qualified, $qualified->qualification_status);
        $this->assertNull($qualified->qualification_last_error);
    }

    public function test_agent_throws_when_prompt_file_is_empty(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();

        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->andReturn(true);
        $file->shouldReceive('get')
            ->andReturn('   ');
        $file->shouldReceive('files')
            ->andReturn([]);

        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification prompt file is empty');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_agent_throws_when_client_is_missing(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();

        Client::addGlobalScope('qualification-missing-client', function ($query): void {
            $query->whereRaw('0 = 1');
        });

        try {
            $agent = app(QualificationAgent::class);
            $expectedMessage = 'Qualification client not found for opportunity: '.$opportunity->id;

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($expectedMessage);

            $agent->handle([
                            'opportunity_id' => $opportunity->id,
            ]);
        } finally {
            $modelReflection = new ReflectionClass(Client::class);
            $scopesProperty = $modelReflection->getProperty('globalScopes');
            $scopes = $scopesProperty->getValue();
            unset($scopes[Client::class]['qualification-missing-client']);
            $scopesProperty->setValue(null, $scopes);
        }
    }

    public function test_agent_throws_when_analysis_response_is_not_structured(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $unstructuredResponse = Mockery::mock(AgentResponse::class);
        $analysisAgent = Mockery::mock(QualificationAnalysisAgent::class);
        $analysisAgent->shouldReceive('prompt')
            ->once()
            ->andReturn($unstructuredResponse);

        $this->app->bind(QualificationAnalysisAgent::class, function () use ($analysisAgent) {
            return $analysisAgent;
        });

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_persist_throws_when_insights_are_not_an_array(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $agent = app(QualificationAgent::class);
        $method = new ReflectionMethod(QualificationAgent::class, 'persistSuccessfulQualification');
        $payload = [
                            'qualification_notes' => 'Notes without insights.',
                            'ai_insights' => null,
        ];

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $method->invoke($agent, $opportunity, $payload);
    }

    public function test_agent_throws_when_opportunity_payload_cannot_be_encoded(): void
    {
        $client = Client::factory()
                        ->create();
        $client->company_name = "\xB1\x31";
        $client->save();

        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create();
        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification opportunity payload could not be encoded.');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_non_markdown_catalog_files_are_skipped(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $skipPath = base_path('docs/services/_coverage_skip.txt');
        file_put_contents($skipPath, 'not a service catalog file');

        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create();

        $opportunityId = (string) $opportunity->id;
        $clientId = (string) $client->id;

        QualificationFake::fakeSuccessful($opportunityId, $clientId);

        try {
            $agent = app(QualificationAgent::class);
            $agent->handle([
                            'opportunity_id' => $opportunity->id,
            ]);
        } finally {
            if (is_file($skipPath)) {
                unlink($skipPath);
            }
        }

        $opportunity->refresh();

        $this->assertSame(QualificationStatus::Qualified, $opportunity->qualification_status);
    }

    public function test_agent_throws_when_prompt_file_is_missing(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();

        File::partialMock()
            ->shouldReceive('exists')
            ->andReturn(false);

        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification prompt file not found');

        $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_approved_prompt_ranks_website_ahead_of_software_and_email(): void
    {
        $promptPath = base_path('docs/prompts/qualification-agent.md');
        $prompt = File::get($promptPath);
        $promptText = (string) $prompt;

        $this->assertStringContainsString('website_design_development` — primary', $promptText);
        $this->assertStringContainsString('custom_software_development` — skip or lowest as the opening', $promptText);
        $this->assertStringContainsString('Do not make email the top opportunity when a website opening exists', $promptText);
    }

    /**
     * @return list<array{service: string, title: string, why_it_matters: string, priority: string}>
     */
    private function catalogOpportunityEntries(): array
    {
        $servicesPath = base_path('docs/services');
        $files = File::files($servicesPath);
        $entries = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $isMarkdown = str_ends_with($filename, '.md');

            if ($isMarkdown === false) {
                continue;
            }

            $service = str_replace('.md', '', $filename);
            $entries[] = [
                            'service' => $service,
                            'title' => 'Catalog service '.$filename,
                            'why_it_matters' => 'Covered in the initial scan.',
                            'priority' => 'medium',
            ];
        }

        return $entries;
    }
}
