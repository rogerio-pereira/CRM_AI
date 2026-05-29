<?php

namespace Tests\Feature\Ai;

use App\Enums\ClientStatus;
use App\Enums\PipelineStage;
use App\Events\ClientCreated;
use App\Jobs\RunProposalAssistantAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Livewire\Opportunities\Index as OpportunitiesIndex;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ClientService;
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

        $user = User::factory()->create();
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Lead,
        ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Qualification->value)
            ->assertHasNoErrors();

        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($opportunity): bool {
            return $job->payload['opportunity_id'] === $opportunity->id
                && $job->payload['to_stage'] === PipelineStage::Qualification->value;
        });
    }

    public function test_moving_opportunity_to_proposal_generation_enqueues_proposal_assistant_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'stage' => PipelineStage::Contact,
        ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::ProposalGeneration->value);

        Queue::assertPushed(RunProposalAssistantAgentJob::class);
    }

    public function test_client_creation_enqueues_qualification_job(): void
    {
        Queue::fake();

        app(ClientService::class)->create([
            'company_name' => 'AI Orchestration Co',
            'status' => ClientStatus::Active,
        ]);

        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job): bool {
            return $job->payload['trigger'] === 'client_created'
                && isset($job->payload['client_id']);
        });
    }

    public function test_stage_change_to_won_does_not_enqueue_ai_jobs(): void
    {
        Queue::fake();

        $opportunity = Opportunity::factory()->create([
            'stage' => PipelineStage::ProposalSent,
        ]);

        app(OpportunityService::class)->moveToStage(
            $opportunity,
            PipelineStage::Won,
        );

        Queue::assertNothingPushed();
    }

    public function test_client_created_listener_can_be_faked_without_queue_work(): void
    {
        Event::fake([ClientCreated::class]);

        app(ClientService::class)->create([
            'company_name' => 'Event Fake Co',
            'status' => ClientStatus::Active,
        ]);

        Event::assertDispatched(ClientCreated::class);
    }
}
