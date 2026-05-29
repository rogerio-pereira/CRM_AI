<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Livewire\Tasks\Index;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_index_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk();
    }

    public function test_user_can_create_a_task(): void
    {
        Event::fake([TaskCreated::class]);

        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('client_id', $client->id)
            ->set('title', 'Prepare proposal')
            ->set('due_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('priority', TaskPriority::High->value)
            ->set('is_important', true)
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'title' => 'Prepare proposal',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Pending->value,
            'is_important' => true,
        ]);

        Event::assertDispatched(TaskCreated::class);
    }

    public function test_title_is_required(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('client_id', $client->id)
            ->set('title', '')
            ->call('saveTask')
            ->assertHasErrors(['title']);
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
            ->set('title', 'Invalid link')
            ->set('due_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveTask')
            ->assertHasErrors(['opportunity_id']);
    }

    public function test_mark_done_updates_status(): void
    {
        Event::fake([TaskUpdated::class]);

        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('markDone', $task->id)
            ->assertHasNoErrors();

        $task->refresh();

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);

        Event::assertDispatched(TaskUpdated::class);
    }

    public function test_mark_done_excludes_task_from_pending_dashboard_query(): void
    {
        $task = Task::factory()->create();
        $service = app(TaskService::class);

        $this->assertCount(1, $service->pendingForDashboard());

        $service->markDone($task);

        $this->assertCount(0, $service->pendingForDashboard());
    }

    public function test_user_can_delete_a_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $task->id)
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_important_flag_is_persisted_on_update(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['is_important' => false]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openEditModal', $task->id)
            ->set('is_important', true)
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertTrue($task->fresh()->is_important);
    }
}
