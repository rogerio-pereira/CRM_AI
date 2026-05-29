<?php

namespace Tests\Feature\Leads;

use App\Enums\TaskStatus;
use App\Livewire\Leads\Index;
use App\Livewire\Tasks\QuickCreateModal;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadDetailTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_modal_lists_tasks_and_add_task_button_opens_quick_create(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'Task History Co']);
        Task::factory()->for($client)->create([
            'title' => 'Existing client task',
            'status' => TaskStatus::Pending,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSee('Existing client task')
            ->assertSee(__('Add task'));

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-task-for-client', clientId: $client->id)
            ->set('title', 'Added from detail')
            ->call('saveTask')
            ->assertDispatched('task-created');

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'title' => 'Added from detail',
        ]);
    }

    public function test_leads_list_actions_menu_can_open_task_quick_create(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'List Task Co']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openTaskModalForClient', $client->id)
            ->assertDispatched('open-task-for-client');

        Livewire::test(QuickCreateModal::class)
            ->dispatch('open-task-for-client', clientId: $client->id)
            ->set('title', 'Added from list menu')
            ->call('saveTask')
            ->assertDispatched('task-created');

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'title' => 'Added from list menu',
        ]);
    }

    public function test_detail_modal_shows_colored_task_status_badge(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $task = Task::factory()->for($client)->create([
            'status' => TaskStatus::Pending,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-task-status-'.$task->id.'"')
            ->assertSeeHtml('data-status="'.TaskStatus::Pending->value.'"')
            ->assertSeeHtml(TaskStatus::Pending->badgeClasses());
    }

    public function test_detail_modal_shows_overdue_task_status_in_danger(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $task = Task::factory()->for($client)->overdue()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-task-status-'.$task->id.'"')
            ->assertSeeHtml('status-danger')
            ->assertSeeHtml('data-test="leads-detail-task-overdue-'.$task->id.'"')
            ->assertSeeHtml('leads-detail-row--overdue');
    }

    public function test_detail_modal_strikes_through_completed_task_row(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $task = Task::factory()->for($client)->done()->create(['title' => 'Finished task']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-task-completed-'.$task->id.'"')
            ->assertSeeHtml('leads-detail-row--completed');
    }

    public function test_detail_modal_shows_important_star_before_task_title(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Task::factory()->for($client)->important()->create([
            'title' => 'Starred task first',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-task-important-')
            ->assertSee('Starred task first');
    }
}
