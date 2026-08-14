<?php

namespace Tests\Unit\Services;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class OpportunityServiceTest extends TestCase
{
    use RefreshDatabase;

    private OpportunityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OpportunityService::class);
    }

    public function test_create_sets_lead_stage_and_open_status(): void
    {
        Event::fake([OpportunityCreated::class]);

        $client = Client::factory()
                        ->create();

        $opportunity = $this->service->create([
            'client_id' => $client->id,
            'title' => 'Created via service',
            'estimated_value' => '5000',
        ]);

        $this->assertSame(PipelineStage::Lead, $opportunity->stage);
        $this->assertSame(OpportunityStatus::Open, $opportunity->status);
        $this->assertSame('Created via service', $opportunity->title);
        Event::assertDispatched(OpportunityCreated::class);
    }

    public function test_update_persists_attributes_and_refreshes_client(): void
    {
        $opportunity = Opportunity::factory()
                            ->create([
                                'title' => 'Original title',
                            ]);

        $updated = $this->service->update($opportunity, [
                                'title' => 'Updated title',
                                'estimated_value' => '9900',
        ]);

        $this->assertSame('Updated title', $updated->title);
        $this->assertSame('9900.00', $updated->estimated_value);
        $this->assertTrue($updated->relationLoaded('client'));
    }

    public function test_move_to_stage_does_nothing_when_stage_is_unchanged(): void
    {
        Event::fake([OpportunityStageChanged::class]);

        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);

        $user = User::factory()
                    ->create();

        $result = $this->service->moveToStage(
            $opportunity,
            PipelineStage::Lead,
            $user->id,
        );

        $this->assertTrue($result->is($opportunity));
        Event::assertNotDispatched(OpportunityStageChanged::class);
    }

    public function test_move_to_stage_sets_lost_status_and_dispatches_event(): void
    {
        Event::fake([OpportunityStageChanged::class]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->open()
                            ->create();

        $this->service->moveToStage(
            $opportunity,
            PipelineStage::Lost,
            $user->id,
        );

        $this->assertDatabaseHas('opportunities', [
                                'id' => $opportunity->id,
                                'stage' => PipelineStage::Lost->value,
                                'status' => OpportunityStatus::Lost->value,
        ]);

        Event::assertDispatched(OpportunityStageChanged::class);
    }

    public function test_move_to_stage_sets_won_status_and_dispatches_event(): void
    {
        Event::fake([OpportunityStageChanged::class]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->open()
                            ->create();

        $this->service->moveToStage(
            $opportunity,
            PipelineStage::Won,
            $user->id,
        );

        $this->assertDatabaseHas('opportunities', [
                                'id' => $opportunity->id,
                                'stage' => PipelineStage::Won->value,
                                'status' => OpportunityStatus::Won->value,
        ]);

        Event::assertDispatched(OpportunityStageChanged::class);
    }

    public function test_grouped_by_stage_returns_all_stages_with_matching_opportunities(): void
    {
        $client = Client::factory()
                        ->create();

        $lead = Opportunity::factory()
                    ->for($client)
                    ->create([
                        'stage' => PipelineStage::Lead,
                    ]);

        $won = Opportunity::factory()
                    ->for($client)
                    ->won()
                    ->create();

        $grouped = $this->service->groupedByStage();
        $leadOpportunities = $grouped[PipelineStage::Lead->value];
        $wonOpportunities = $grouped[PipelineStage::Won->value];
        $qualificationOpportunities = $grouped[PipelineStage::Qualification->value];

        $this->assertCount(8, $grouped);
        $this->assertTrue(
            $leadOpportunities->contains(
                fn (Opportunity $item): bool => $item->is($lead),
            ),
        );
        $this->assertTrue(
            $wonOpportunities->contains(
                fn (Opportunity $item): bool => $item->is($won),
            ),
        );
        $this->assertTrue($qualificationOpportunities->isEmpty());
    }

    public function test_create_throws_when_opportunity_cannot_be_reloaded(): void
    {
        Event::fake([OpportunityCreated::class]);

        Opportunity::created(function (Opportunity $opportunity): void {
            $opportunity->exists = false;
        });

        $client = Client::factory()
                        ->create();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Created opportunity could not be reloaded.');

            $this->service->create([
                                'client_id' => $client->id,
                                'title' => 'Vanishing deal',
            ]);
        } finally {
            Opportunity::flushEventListeners();
        }
    }

    public function test_move_to_stage_throws_when_opportunity_cannot_be_reloaded(): void
    {
        Event::fake([OpportunityStageChanged::class]);

        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Lead,
                            ]);

        Opportunity::saved(function (Opportunity $model) use ($opportunity): void {
            $modelKey = $model->getKey();
            $opportunityKey = $opportunity->getKey();

            if ($modelKey !== $opportunityKey) {
                return;
            }

            $model->exists = false;
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Updated opportunity could not be reloaded.');

            $this->service->moveToStage(
                $opportunity,
                PipelineStage::Qualification,
            );
        } finally {
            Opportunity::flushEventListeners();
        }
    }
}
