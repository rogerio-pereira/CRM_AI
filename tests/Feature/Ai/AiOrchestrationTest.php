<?php

namespace Tests\Feature\Ai;

use App\Enums\PipelineStage;
use App\Events\OpportunityCreated;
use App\Jobs\RunProposalAssistantAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Jobs\RunRecommendationAgentJob;
use App\Livewire\Opportunities\Index as OpportunitiesIndex;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AiOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_opportunity_to_qualification_enqueues_qualification_job(): void
    {
        Queue::fake();

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Qualification->value)
            ->assertHasNoErrors();

        Queue::assertPushed(RunQualificationAgentJob::class, 1);
        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($opportunity): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'];
            $targetStage = $job->payload['to_stage'];

            if ($payloadOpportunityId !== $opportunity->id) {
                return false;
            }

            return $targetStage === PipelineStage::Qualification->value;
        });
    }

    public function test_moving_opportunity_to_qualification_skips_job_when_already_processing(): void
    {
        Queue::fake();

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationProcessing()
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Qualification->value)
            ->assertHasNoErrors();

        Queue::assertNotPushed(RunQualificationAgentJob::class);
    }

    public function test_moving_opportunity_to_qualification_skips_job_when_already_qualified(): void
    {
        Queue::fake();

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Qualification->value)
            ->assertHasNoErrors();

        Queue::assertNotPushed(RunQualificationAgentJob::class);
    }

    public function test_moving_a_new_opportunity_to_qualification_enqueues_even_when_a_sibling_is_qualified(): void
    {
        Queue::fake();

        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();
        Opportunity::factory()
            ->for($client)
            ->qualificationQualified()
            ->create([
                'stage' => PipelineStage::Contact,
            ]);
        $newDeal = Opportunity::factory()
                        ->for($client)
                        ->create([
                            'stage' => PipelineStage::Lead,
                        ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $newDeal->id, PipelineStage::Qualification->value)
            ->assertHasNoErrors();

        Queue::assertPushed(RunQualificationAgentJob::class, 1);
        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($newDeal): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;

            return $payloadOpportunityId === $newDeal->id;
        });
    }

    public function test_moving_opportunity_to_proposal_generation_enqueues_proposal_assistant_job(): void
    {
        Queue::fake();

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Contact,
                            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::ProposalGeneration->value);

        Queue::assertPushed(RunProposalAssistantAgentJob::class);
    }

    public function test_moving_opportunity_to_proposal_analysis_enqueues_recommendation_job(): void
    {
        Queue::fake();

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::ProposalGeneration,
                            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::ProposalAnalysis->value)
            ->assertHasNoErrors();

        Queue::assertPushed(RunRecommendationAgentJob::class, function (RunRecommendationAgentJob $job) use ($opportunity): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'];
            $targetStage = $job->payload['to_stage'];

            if ($payloadOpportunityId !== $opportunity->id) {
                return false;
            }

            return $targetStage === PipelineStage::ProposalAnalysis->value;
        });
    }

    public function test_opportunity_creation_enqueues_qualification_job(): void
    {
        Queue::fake();

        $client = Client::factory()
                        ->create();
        $opportunityService = app(OpportunityService::class);
        $opportunity = $opportunityService->create([
                                'client_id' => $client->id,
                                'title' => 'AI Orchestration Deal',
        ]);

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

    public function test_stage_change_to_won_does_not_enqueue_ai_jobs(): void
    {
        Queue::fake();

        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::ProposalSent,
                            ]);

        $opportunityService = app(OpportunityService::class);

        $opportunityService->moveToStage(
            $opportunity,
            PipelineStage::Won,
        );

        Queue::assertNothingPushed();
    }

    public function test_opportunity_created_listener_can_be_faked_without_queue_work(): void
    {
        Event::fake([OpportunityCreated::class]);

        $client = Client::factory()
                        ->create();
        $opportunityService = app(OpportunityService::class);

        $opportunityService->create([
                                'client_id' => $client->id,
                                'title' => 'Event Fake Deal',
        ]);

        Event::assertDispatched(OpportunityCreated::class);
    }

    public function test_opportunity_created_has_a_single_listener(): void
    {
        $listeners = Event::getListeners(OpportunityCreated::class);

        $this->assertCount(1, $listeners);
    }
}
