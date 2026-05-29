<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskStatus;
use App\Livewire\Tasks\QuickCreateModal;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickCreateModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_for_opportunity_prefills_client_and_opportunity(): void
    {
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create(['title' => 'Kanban Opp']);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-task-for-opportunity', opportunityId: $opportunity->id)
            ->assertSet('showFormModal', true)
            ->assertSet('client_id', $client->id)
            ->assertSet('opportunity_id', $opportunity->id);
    }

    public function test_open_for_client_prefills_client_only(): void
    {
        $client = Client::factory()->create(['company_name' => 'Detail Client Co']);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-task-for-client', clientId: $client->id)
            ->assertSet('showFormModal', true)
            ->assertSet('client_id', $client->id)
            ->assertSet('opportunity_id', null);
    }

    public function test_save_creates_task_and_dispatches_event(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create();

        $this->actingAs($user);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-task-for-opportunity', opportunityId: $opportunity->id)
            ->set('title', 'From Kanban quick create')
            ->call('saveTask')
            ->assertDispatched('task-created')
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'title' => 'From Kanban quick create',
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_save_from_client_prefills_creates_task_without_opportunity(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-task-for-client', clientId: $client->id)
            ->set('title', 'Client-only task')
            ->call('saveTask')
            ->assertHasNoErrors()
            ->assertDispatched('task-created');

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'opportunity_id' => null,
            'title' => 'Client-only task',
            'status' => TaskStatus::Pending->value,
        ]);
    }
}
