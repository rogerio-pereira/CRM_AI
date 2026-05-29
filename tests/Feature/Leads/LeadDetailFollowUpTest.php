<?php

namespace Tests\Feature\Leads;

use App\Enums\FollowUpReminderStatus;
use App\Livewire\FollowUps\QuickCreateModal;
use App\Livewire\Leads\Index;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadDetailFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_modal_lists_follow_ups_and_add_button_opens_quick_create(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'Follow-up History Co']);
        $followUp = FollowUp::factory()->for($client)->create([
            'reminder_status' => FollowUpReminderStatus::Pending,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSee(FollowUpReminderStatus::Pending->label())
            ->assertSeeHtml('data-test="leads-detail-follow-up-status-'.$followUp->id.'"')
            ->assertSeeHtml('data-status="'.FollowUpReminderStatus::Pending->value.'"')
            ->assertSeeHtml(FollowUpReminderStatus::Pending->badgeClasses())
            ->assertSee(__('Add follow-up'));

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-follow-up-for-client', clientId: $client->id)
            ->set('notes', 'Added from detail')
            ->call('saveFollowUp')
            ->assertDispatched('follow-up-created');

        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'notes' => 'Added from detail',
        ]);
    }

    public function test_leads_list_actions_menu_can_open_follow_up_quick_create(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'List Follow-up Co']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openFollowUpModalForClient', $client->id)
            ->assertDispatched('open-follow-up-for-client');

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-follow-up-for-client', clientId: $client->id)
            ->set('notes', 'Added from list menu')
            ->call('saveFollowUp')
            ->assertDispatched('follow-up-created');

        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'notes' => 'Added from list menu',
        ]);
    }

    public function test_detail_modal_strikes_through_completed_follow_up_row(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $followUp = FollowUp::factory()->for($client)->completed()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-follow-up-completed-'.$followUp->id.'"')
            ->assertSeeHtml('leads-detail-row--completed');
    }

    public function test_detail_modal_shows_overdue_follow_up_status_in_danger(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $followUp = FollowUp::factory()->for($client)->overdue()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-follow-up-status-'.$followUp->id.'"')
            ->assertSeeHtml('status-danger')
            ->assertSeeHtml('data-test="leads-detail-follow-up-overdue-'.$followUp->id.'"')
            ->assertSeeHtml('leads-detail-row--overdue');
    }

    public function test_follow_up_created_refreshes_detail_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->dispatch('follow-up-created')
            ->assertSet('detailClientId', $client->id);
    }
}
