<?php

namespace Tests\Feature\FollowUps;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpReminderStatus;
use App\Events\FollowUpCreated;
use App\Events\FollowUpUpdated;
use App\Livewire\FollowUps\Index;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class FollowUpManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_ups_index_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('follow-ups.index'))
            ->assertOk();
    }

    public function test_user_can_create_a_follow_up(): void
    {
        Event::fake([FollowUpCreated::class]);

        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('client_id', $client->id)
            ->set('due_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('priority', FollowUpPriority::High->value)
            ->set('notes', 'Call back tomorrow')
            ->call('saveFollowUp')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('follow_ups', [
            'client_id' => $client->id,
            'priority' => FollowUpPriority::High->value,
            'reminder_status' => FollowUpReminderStatus::Pending->value,
            'notes' => 'Call back tomorrow',
        ]);

        Event::assertDispatched(FollowUpCreated::class);
    }

    public function test_due_date_is_required(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('client_id', $client->id)
            ->set('due_at', '')
            ->call('saveFollowUp')
            ->assertHasErrors(['due_at']);
    }

    public function test_opportunity_must_belong_to_selected_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($otherClient)->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('client_id', $client->id)
            ->set('opportunity_id', $opportunity->id)
            ->set('due_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveFollowUp')
            ->assertHasErrors(['opportunity_id']);
    }

    public function test_mark_complete_updates_reminder_status(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $user = User::factory()->create();
        $followUp = FollowUp::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('markComplete', $followUp->id)
            ->assertHasNoErrors();

        $followUp->refresh();

        $this->assertSame(FollowUpReminderStatus::Completed, $followUp->reminder_status);
        $this->assertNotNull($followUp->completed_at);

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_snooze_updates_reminder_status(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $user = User::factory()->create();
        $followUp = FollowUp::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('snooze', $followUp->id)
            ->assertHasNoErrors();

        $followUp->refresh();

        $this->assertSame(FollowUpReminderStatus::Snoozed, $followUp->reminder_status);
        $this->assertNotNull($followUp->snoozed_until);

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_overdue_filter_returns_only_overdue_pending_follow_ups(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        FollowUp::factory()->for($client)->create([
            'due_at' => now()->addDay(),
        ]);

        $overdue = FollowUp::factory()->for($client)->overdue()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('overdueOnly', true)
            ->assertSee($overdue->client->company_name);
    }
}
