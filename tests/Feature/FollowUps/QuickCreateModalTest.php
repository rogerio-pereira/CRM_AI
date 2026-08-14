<?php

namespace Tests\Feature\FollowUps;

use App\Enums\FollowUpReminderStatus;
use App\Livewire\FollowUps\QuickCreateModal;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickCreateModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_for_client_prefills_client_without_opportunity(): void
    {
        $client = Client::factory()
                        ->create(['company_name' => 'Follow-up Client Co']);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-follow-up-for-client', clientId: $client->id)
            ->assertSet('showFormModal', true)
            ->assertSet('client_id', $client->id)
            ->assertSet('opportunity_id', null);
    }

    public function test_open_for_opportunity_prefills_client_and_opportunity(): void
    {
        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create(['title' => 'Kanban Opp']);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-follow-up-for-opportunity', opportunityId: $opportunity->id)
            ->assertSet('showFormModal', true)
            ->assertSet('client_id', $client->id)
            ->assertSet('opportunity_id', $opportunity->id);
    }

    public function test_save_creates_follow_up_and_dispatches_event(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create();

        $this->actingAs($user);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-follow-up-for-opportunity', opportunityId: $opportunity->id)
            ->set('notes', 'From Kanban quick create')
            ->call('saveFollowUp')
            ->assertDispatched('follow-up-created')
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'notes' => 'From Kanban quick create',
            'reminder_status' => FollowUpReminderStatus::Pending->value,
        ]);
    }

    public function test_changing_client_clears_opportunity(): void
    {
        $firstClient = Client::factory()
                    ->create();
        $secondClient = Client::factory()
                    ->create();
        $firstOpportunity = Opportunity::factory()
                    ->for($firstClient)
                    ->create(['title' => 'First deal']);

        Opportunity::factory()
            ->for($secondClient)
            ->create(['title' => 'Second deal']);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-follow-up-for-opportunity', opportunityId: $firstOpportunity->id)
            ->set('client_id', $secondClient->id)
            ->assertSet('opportunity_id', null)
            ->assertCount('opportunityOptions', 1)
            ->assertSee('Second deal');
    }

    public function test_save_without_opportunity_creates_follow_up_for_client_only(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();
        $dueAt = now()
                        ->addDay()
                        ->format('Y-m-d\TH:i');

        $this->actingAs($user);

        Livewire::test(QuickCreateModal::class)
            ->set('client_id', $client->id)
            ->set('opportunity_id', '')
            ->set('due_at', $dueAt)
            ->call('saveFollowUp')
            ->assertHasNoErrors()
            ->assertDispatched('follow-up-created');

        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'opportunity_id' => null,
            'reminder_status' => FollowUpReminderStatus::Pending->value,
        ]);
    }
}
