<?php

namespace Tests\Unit\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaskService::class);
    }

    public function test_create_sets_pending_status_and_dispatches_event(): void
    {
        Event::fake([TaskCreated::class]);

        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create();

        $task = $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'title' => 'Call client',
            'due_at' => now()->addDay(),
            'priority' => TaskPriority::High->value,
            'is_important' => true,
        ]);

        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertTrue($task->is_important);
        $this->assertSame($opportunity->id, $task->opportunity_id);

        Event::assertDispatched(TaskCreated::class);
    }

    public function test_create_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($otherClient)->create();

        $this->expectException(ValidationException::class);

        $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'title' => 'Mismatch',
            'due_at' => now()->addDay(),
            'priority' => TaskPriority::Medium->value,
        ]);
    }

    public function test_mark_done_sets_status_and_completed_at(): void
    {
        Event::fake([TaskUpdated::class]);

        $task = Task::factory()->create();

        $result = $this->service->markDone($task);

        $this->assertSame(TaskStatus::Done, $result->status);
        $this->assertNotNull($result->completed_at);

        Event::assertDispatched(TaskUpdated::class);
    }

    public function test_pending_for_dashboard_orders_by_due_at_and_limits_results(): void
    {
        $client = Client::factory()->create();

        $later = Task::factory()->for($client)->create([
            'due_at' => now()->addDays(3),
            'title' => 'Later task',
        ]);

        $sooner = Task::factory()->for($client)->create([
            'due_at' => now()->addDay(),
            'title' => 'Sooner task',
        ]);

        Task::factory()->for($client)->done()->create();

        $results = $this->service->pendingForDashboard();

        $this->assertCount(2, $results);
        $this->assertTrue($results->first()->is($sooner));
        $this->assertTrue($results->pluck('id')->contains($later->id));

        $limited = $this->service->pendingForDashboard(1);

        $this->assertCount(1, $limited);
        $this->assertTrue($limited->first()->is($sooner));
    }

    public function test_update_persists_attributes_and_dispatches_event(): void
    {
        Event::fake([TaskUpdated::class]);

        $client = Client::factory()->create();
        $task = Task::factory()->for($client)->create(['title' => 'Original title']);

        $updated = $this->service->update($task, [
            'title' => 'Updated title',
            'priority' => TaskPriority::Low->value,
        ]);

        $this->assertSame('Updated title', $updated->title);
        $this->assertSame(TaskPriority::Low, $updated->priority);

        Event::assertDispatched(TaskUpdated::class);
    }

    public function test_update_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $task = Task::factory()->for($client)->create();
        $opportunity = Opportunity::factory()->for($otherClient)->create();

        $this->expectException(ValidationException::class);

        $this->service->update($task, [
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_delete_removes_task(): void
    {
        $task = Task::factory()->create();

        $this->service->delete($task);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_create_without_opportunity_id(): void
    {
        $client = Client::factory()->create();

        $task = $this->service->create([
            'client_id' => $client->id,
            'title' => 'No opportunity',
            'due_at' => now()->addDay(),
            'priority' => TaskPriority::Medium->value,
        ]);

        $this->assertNull($task->opportunity_id);
    }

    public function test_create_with_null_opportunity_id(): void
    {
        $client = Client::factory()->create();

        $task = $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => null,
            'title' => 'Explicit null opportunity',
            'due_at' => now()->addDay(),
            'priority' => TaskPriority::Medium->value,
        ]);

        $this->assertNull($task->opportunity_id);
    }

    public function test_assert_opportunity_belongs_to_client_skips_when_opportunity_is_not_found(): void
    {
        $method = new \ReflectionMethod($this->service, 'assertOpportunityBelongsToClient');
        $method->setAccessible(true);

        $method->invoke($this->service, [
            'client_id' => 1,
            'opportunity_id' => 999_999,
        ]);

        $this->assertTrue(true);
    }

    public function test_assert_opportunity_belongs_to_client_skips_when_opportunity_id_is_absent_or_null(): void
    {
        $method = new \ReflectionMethod($this->service, 'assertOpportunityBelongsToClient');
        $method->setAccessible(true);

        $method->invoke($this->service, ['client_id' => 1]);
        $method->invoke($this->service, [
            'client_id' => 1,
            'opportunity_id' => null,
        ]);

        $this->assertTrue(true);
    }

    public function test_paginate_filters_by_search_on_title(): void
    {
        $client = Client::factory()->create();

        $matching = Task::factory()->for($client)->create(['title' => 'Unique Alpha Task']);
        Task::factory()->for($client)->create(['title' => 'Other task']);

        $results = $this->service->paginateForIndex(
            search: 'alpha',
            priorityFilter: null,
            pendingOnly: false,
        );

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($matching));
    }

    public function test_paginate_filters_by_search_on_client_company_name(): void
    {
        $matchingClient = Client::factory()->create(['company_name' => 'Searchable Client Ltd']);
        $otherClient = Client::factory()->create(['company_name' => 'Other Co']);

        $matching = Task::factory()->for($matchingClient)->create(['title' => 'Any title']);
        Task::factory()->for($otherClient)->create();

        $results = $this->service->paginateForIndex(
            search: 'searchable client',
            priorityFilter: null,
            pendingOnly: false,
        );

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($matching));
    }

    public function test_paginate_filters_by_priority(): void
    {
        $client = Client::factory()->create();

        $high = Task::factory()->for($client)->create(['priority' => TaskPriority::High]);
        Task::factory()->for($client)->create(['priority' => TaskPriority::Low]);

        $results = $this->service->paginateForIndex(
            search: null,
            priorityFilter: TaskPriority::High->value,
            pendingOnly: false,
        );

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($high));
    }

    public function test_paginate_pending_only_excludes_done_tasks(): void
    {
        $client = Client::factory()->create();

        $pending = Task::factory()->for($client)->create(['status' => TaskStatus::Pending]);
        Task::factory()->for($client)->done()->create();

        $results = $this->service->paginateForIndex(null, null, pendingOnly: true, hideDone: false);

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($pending));
    }

    public function test_paginate_includes_done_tasks_when_hide_done_is_false(): void
    {
        $client = Client::factory()->create();

        Task::factory()->for($client)->create(['status' => TaskStatus::Pending]);
        $done = Task::factory()->for($client)->done()->create();

        $results = $this->service->paginateForIndex(null, null, pendingOnly: false, hideDone: false);

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('id')->contains($done->id));
    }
}
