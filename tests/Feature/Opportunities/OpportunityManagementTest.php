<?php

namespace Tests\Feature\Opportunities;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Events\OpportunityStageChanged;
use App\Livewire\Opportunities\Index;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunities_index_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('opportunities.index'))
            ->assertOk();
    }

    public function test_user_can_create_an_opportunity_in_lead_stage(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('title', 'New enterprise deal')
            ->set('client_id', $client->id)
            ->set('estimated_value', '15000')
            ->call('saveOpportunity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'title' => 'New enterprise deal',
            'client_id' => $client->id,
            'stage' => PipelineStage::Lead->value,
            'status' => OpportunityStatus::Open->value,
            'estimated_value' => '15000.00',
        ]);
    }

    public function test_title_is_required_when_creating_an_opportunity(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('title', '')
            ->set('client_id', $client->id)
            ->call('saveOpportunity')
            ->assertHasErrors(['title']);
    }

    public function test_client_must_exist_when_creating_an_opportunity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('title', 'Invalid client deal')
            ->set('client_id', 99999)
            ->call('saveOpportunity')
            ->assertHasErrors(['client_id']);
    }

    public function test_move_to_stage_updates_database_and_dispatches_event(): void
    {
        Event::fake([OpportunityStageChanged::class]);

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'stage' => PipelineStage::Lead,
            'status' => OpportunityStatus::Open,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Qualification->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => PipelineStage::Qualification->value,
            'status' => OpportunityStatus::Open->value,
        ]);

        Event::assertDispatched(OpportunityStageChanged::class);
    }

    public function test_moving_to_won_sets_terminal_status(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->open()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Won->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => PipelineStage::Won->value,
            'status' => OpportunityStatus::Won->value,
        ]);
    }

    public function test_moving_out_of_terminal_stage_resets_status_to_open(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->won()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Contact->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => PipelineStage::Contact->value,
            'status' => OpportunityStatus::Open->value,
        ]);
    }
}
