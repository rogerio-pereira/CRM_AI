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
use Illuminate\Support\Facades\Queue;
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
        Queue::fake();

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

    public function test_user_can_update_an_opportunity(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create([
            'title' => 'Original deal',
            'estimated_value' => '1000',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openEditModal', $opportunity->id)
            ->assertSet('title', 'Original deal')
            ->assertSet('estimated_value', '1000.00')
            ->set('title', 'Updated deal')
            ->set('estimated_value', '2500')
            ->call('saveOpportunity')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'title' => 'Updated deal',
            'estimated_value' => '2500.00',
        ]);
    }

    public function test_open_edit_modal_clears_estimated_value_when_null(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'estimated_value' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openEditModal', $opportunity->id)
            ->assertSet('estimated_value', '');
    }

    public function test_save_opportunity_stores_null_when_estimated_value_is_blank(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('title', 'No value deal')
            ->set('client_id', $client->id)
            ->set('estimated_value', '   ')
            ->call('saveOpportunity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'title' => 'No value deal',
            'estimated_value' => null,
        ]);
    }

    public function test_estimated_value_must_be_numeric(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('title', 'Invalid value deal')
            ->set('client_id', $client->id)
            ->set('estimated_value', 'not-a-number')
            ->call('saveOpportunity')
            ->assertHasErrors(['estimated_value']);
    }

    public function test_open_detail_modal_loads_selected_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create(['title' => 'Detail target deal']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSet('detailOpportunityId', $opportunity->id)
            ->assertSet('showDetailModal', true)
            ->assertSet('detailOpportunity.title', 'Detail target deal');
    }

    public function test_opportunity_detail_renders_client_contact_summary(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'company_name' => 'Summary Contact Co',
            'contact_name' => 'Jordan Contact',
            'contact_email' => 'jordan@summary.test',
            'contact_phone' => '813-555-0199',
            'website' => 'https://summary-contact.test',
        ]);
        $opportunity = Opportunity::factory()->for($client)->create([
            'title' => 'Summary contact deal',
        ]);

        $this->actingAs($user);

        $statusDescription = $opportunity->status->description();

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="opportunities-detail-company-name"')
            ->assertSee('Summary Contact Co')
            ->assertSeeHtml('data-test="opportunities-detail-contact-name"')
            ->assertSee('Jordan Contact')
            ->assertSeeHtml('data-test="opportunities-detail-contact-email"')
            ->assertSee('jordan@summary.test')
            ->assertSeeHtml('data-test="opportunities-detail-contact-phone"')
            ->assertSee('813-555-0199')
            ->assertSeeHtml('data-test="opportunities-detail-website-link"')
            ->assertSeeHtml('href="https://summary-contact.test"')
            ->assertSee('https://summary-contact.test')
            ->assertSeeHtml('data-test="opportunities-detail-status-badge"')
            ->assertSee($statusDescription);
    }

    public function test_opportunity_detail_renders_client_qualification_chip(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'company_name' => 'Failed Qualify Client',
        ]);
        $opportunity = Opportunity::factory()->for($client)->qualificationFailed()->create([
            'title' => 'Failed qualify deal',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="opportunities-detail-qualification-badge"')
            ->assertSeeHtml('data-status="failed"')
            ->assertSeeHtml('data-test="opportunities-detail-qualification-error"')
            ->assertSee('Qualification could not be completed. The team can try again later.');
    }

    public function test_moving_to_lost_sets_terminal_status(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->open()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('moveToStage', $opportunity->id, PipelineStage::Lost->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => PipelineStage::Lost->value,
            'status' => OpportunityStatus::Lost->value,
        ]);
    }

    public function test_follow_up_created_event_refreshes_kanban(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'title' => 'Follow-up refresh deal',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Follow-up refresh deal')
            ->dispatch('follow-up-created')
            ->assertSee('Follow-up refresh deal');
    }

    public function test_task_created_event_refreshes_kanban(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'title' => 'Task refresh deal',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Task refresh deal')
            ->dispatch('task-created')
            ->assertSee('Task refresh deal');
    }

    public function test_detail_opportunity_is_null_when_record_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('detailOpportunityId', 99999)
            ->assertSet('detailOpportunity', null);
    }
}
