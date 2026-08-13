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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Support\QualificationFake;
use Tests\TestCase;

class QualificationAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_qualification_updates_lead_advances_stage_and_dispatches_recommendation(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $client = Client::factory()->create([
            'company_name' => 'GreenSprout Lawn Care',
            'website' => 'https://greensprout.example',
        ]);
        $opportunity = Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Lead,
        ]);

        QualificationFake::fakeSuccessful((string) $client->id);

        $agent = app(QualificationAgent::class);
        $result = $agent->handle([
            'client_id' => $client->id,
        ]);

        $client->refresh();
        $opportunity->refresh();

        $this->assertSame('qualified', $result['status']);
        $this->assertSame(QualificationStatus::Qualified, $client->qualification_status);
        $this->assertSame(
            'Local service business with a weak website and referral-heavy growth.',
            $client->qualification_notes,
        );
        $this->assertNotNull($client->qualified_at);
        $this->assertNull($client->qualification_last_error);

        $insights = $client->ai_insights;
        $this->assertIsArray($insights);
        $this->assertSame(1, $insights['schema_version']);
        $this->assertSame('qualification', $insights['source_agent']);
        $this->assertSame(
            'A simple way to bring in more local conversations',
            $insights['outreach_strategy']['contact_example']['subject'],
        );

        $this->assertSame(PipelineStage::Contact, $opportunity->stage);

        Queue::assertPushed(RunRecommendationAgentJob::class, function (RunRecommendationAgentJob $job) use ($client): bool {
            $payloadClientId = $job->payload['client_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;

            return $payloadClientId === $client->id
                && $trigger === 'qualification_completed';
        });
    }

    public function test_manual_lead_creation_enqueues_qualification(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $client = app(ClientService::class)->create([
            'company_name' => 'Manual Qualify Co',
        ]);

        $this->assertSame(QualificationStatus::Pending, $client->qualification_status);

        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($client): bool {
            $payloadClientId = $job->payload['client_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;

            return $payloadClientId === $client->id
                && $trigger === 'client_created';
        });
    }

    public function test_already_qualified_lead_skips_ai_and_still_advances_leftover_opportunities(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $client = Client::factory()->qualificationQualified()->create();
        $opportunity = Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Qualification,
        ]);

        $agent = app(QualificationAgent::class);
        $result = $agent->handle([
            'client_id' => $client->id,
        ]);

        $opportunity->refresh();

        $this->assertSame('already_qualified', $result['status']);
        $this->assertSame(PipelineStage::Contact, $opportunity->stage);
        Queue::assertNothingPushed();
    }

    public function test_failed_ai_status_throws_user_safe_exception(): void
    {
        $client = Client::factory()->create();
        Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Lead,
        ]);

        QualificationFake::fakeFailed('Not enough public information to qualify this lead.');

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Not enough public information to qualify this lead.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_job_failed_callback_stores_user_safe_error_on_the_lead(): void
    {
        $client = Client::factory()->qualificationProcessing()->create();
        $opportunity = Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Qualification,
        ]);

        $job = new RunQualificationAgentJob([
            'client_id' => $client->id,
        ]);
        $exception = new QualificationFailedException('Not enough public information to qualify this lead.');

        $job->failed($exception);

        $client->refresh();
        $opportunity->refresh();

        $this->assertSame(QualificationStatus::Failed, $client->qualification_status);
        $this->assertSame(
            'Not enough public information to qualify this lead.',
            $client->qualification_last_error,
        );
        $this->assertSame(PipelineStage::Qualification, $opportunity->stage);
    }

    public function test_job_failed_callback_uses_generic_error_for_provider_exceptions(): void
    {
        $client = Client::factory()->qualificationProcessing()->create();

        $job = new RunQualificationAgentJob([
            'client_id' => $client->id,
        ]);

        $job->failed(new RuntimeException('OpenAI 500 internal provider dump'));

        $client->refresh();

        $this->assertSame(QualificationStatus::Failed, $client->qualification_status);
        $this->assertSame(
            'Qualification could not be completed. The team can try again later.',
            $client->qualification_last_error,
        );
        $this->assertStringNotContainsString('OpenAI', (string) $client->qualification_last_error);
    }

    public function test_missing_contact_example_is_treated_as_incomplete_output(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['ai_insights']['outreach_strategy']['contact_example']['body'] = '';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_missing_outreach_strategy_is_incomplete(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['ai_insights']['outreach_strategy'] = null;

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_missing_contact_example_object_is_incomplete(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['ai_insights']['outreach_strategy']['contact_example'] = null;

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_empty_contact_subject_is_incomplete(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['ai_insights']['outreach_strategy']['contact_example']['subject'] = '  ';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_agent_throws_when_client_id_is_missing(): void
    {
        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification requires a client_id.');

        $agent->handle([]);
    }

    public function test_agent_throws_when_client_is_missing(): void
    {
        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification client not found');

        $agent->handle([
            'client_id' => 999999,
        ]);
    }

    public function test_failed_status_without_error_uses_default_message(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::failedPayload('');
        $payload['qualification_last_error'] = '   ';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('The lead could not be qualified from the available information.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_unknown_qualification_status_is_incomplete(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['qualification_status'] = 'maybe';

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_missing_insights_is_incomplete(): void
    {
        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['ai_insights'] = null;

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);

        $this->expectException(QualificationFailedException::class);
        $this->expectExceptionMessage('Qualification output was incomplete.');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_empty_notes_are_stored_as_null_and_missing_insight_meta_is_filled(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $client = Client::factory()->create();
        $payload = QualificationFake::successfulPayload((string) $client->id);
        $payload['qualification_notes'] = '   ';
        unset($payload['ai_insights']['generated_at']);
        unset($payload['ai_insights']['source_agent']);
        unset($payload['ai_insights']['schema_version']);

        QualificationAnalysisAgent::fake([
            $payload,
        ]);

        $agent = app(QualificationAgent::class);
        $agent->handle([
            'client_id' => $client->id,
        ]);

        $client->refresh();

        $this->assertNull($client->qualification_notes);
        $this->assertSame(QualificationStatus::Qualified, $client->qualification_status);
        $this->assertSame('qualification', $client->ai_insights['source_agent']);
        $this->assertSame(1, $client->ai_insights['schema_version']);
        $this->assertNotSame('', $client->ai_insights['generated_at']);
    }

    public function test_job_failed_callback_ignores_missing_client_and_already_qualified_leads(): void
    {
        $qualified = Client::factory()->qualificationQualified()->create();

        $missingClientJob = new RunQualificationAgentJob([]);
        $missingClientJob->failed(new RuntimeException('ignored'));

        $unknownClientJob = new RunQualificationAgentJob([
            'client_id' => 999999,
        ]);
        $unknownClientJob->failed(new RuntimeException('ignored'));

        $qualifiedJob = new RunQualificationAgentJob([
            'client_id' => $qualified->id,
        ]);
        $qualifiedJob->failed(new RuntimeException('should not overwrite'));

        $qualified->refresh();

        $this->assertSame(QualificationStatus::Qualified, $qualified->qualification_status);
        $this->assertNull($qualified->qualification_last_error);
    }

    public function test_agent_throws_when_prompt_file_is_empty(): void
    {
        $client = Client::factory()->create();

        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->andReturn(true);
        $file->shouldReceive('get')
            ->andReturn('   ');

        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification prompt file is empty');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }

    public function test_agent_throws_when_prompt_file_is_missing(): void
    {
        $client = Client::factory()->create();

        File::partialMock()
            ->shouldReceive('exists')
            ->andReturn(false);

        $agent = app(QualificationAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Qualification prompt file not found');

        $agent->handle([
            'client_id' => $client->id,
        ]);
    }
}
