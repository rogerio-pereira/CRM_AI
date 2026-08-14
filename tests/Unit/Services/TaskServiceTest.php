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
use Carbon\Carbon;
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

        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create();
        $dueAt = Carbon::now()
                        ->addDay();

        $task = $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'title' => 'Call client',
            'due_at' => $dueAt,
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
        $client = Client::factory()
                        ->create();

        $otherClient = Client::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->for($otherClient)
                            ->create();
        $dueAt = Carbon::now()
                        ->addDay();

        $this->expectException(ValidationException::class);

        $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'title' => 'Mismatch',
            'due_at' => $dueAt,
            'priority' => TaskPriority::Medium->value,
        ]);
    }

    public function test_mark_done_sets_status_and_completed_at(): void
    {
        Event::fake([TaskUpdated::class]);

        $task = Task::factory()
                    ->create();

        $result = $this->service->markDone($task);

        $this->assertSame(TaskStatus::Done, $result->status);
        $this->assertNotNull($result->completed_at);

        Event::assertDispatched(TaskUpdated::class);
    }

    public function test_pending_for_dashboard_orders_by_due_at_and_limits_results(): void
    {
        $client = Client::factory()
                        ->create();
        $laterDueAt = Carbon::now()
                            ->addDays(3);
        $soonerDueAt = Carbon::now()
                            ->addDay();

        $later = Task::factory()
                        ->for($client)
                        ->create([
                            'due_at' => $laterDueAt,
                            'title' => 'Later task',
                        ]);

        $sooner = Task::factory()
                        ->for($client)
                        ->create([
                            'due_at' => $soonerDueAt,
                            'title' => 'Sooner task',
                        ]);

        Task::factory()
                ->for($client)
                ->done()
                ->create();

        $results = $this->service->pendingForDashboard();

        $this->assertCount(2, $results);
        $firstResult = $results->first();
        $resultIds = $results->pluck('id');

        $this->assertTrue($firstResult->is($sooner));
        $this->assertTrue($resultIds->contains($later->id));

        $limited = $this->service->pendingForDashboard(1);

        $this->assertCount(1, $limited);
        $firstLimitedResult = $limited->first();

        $this->assertTrue($firstLimitedResult->is($sooner));
    }

    public function test_paginate_for_index_filters_by_search_title_and_client_name(): void
    {
        $matchingClient = Client::factory()
                        ->create(['company_name' => 'Acme Search Co']);

        $otherClient = Client::factory()
                    ->create(['company_name' => 'Other Co']);

        $byTitle = Task::factory()
                        ->for($otherClient)
                        ->create(['title' => 'Unique Alpha Task']);

        $byClient = Task::factory()
                        ->for($matchingClient)
                        ->create(['title' => 'Generic task']);

        Task::factory()
                ->for($otherClient)
                ->create(['title' => 'Unrelated task']);

        $titleResults = $this->service->paginateForIndex('alpha', null, false, false);
        $clientResults = $this->service->paginateForIndex('acme', null, false, false);

        $titleResultIds = $titleResults->pluck('id');

        $this->assertTrue($titleResultIds->contains($byTitle->id));
        $this->assertFalse($titleResultIds->contains($byClient->id));

        $clientResultIds = $clientResults->pluck('id');

        $this->assertTrue($clientResultIds->contains($byClient->id));
        $this->assertFalse($clientResultIds->contains($byTitle->id));
    }

    public function test_paginate_for_index_filters_by_priority_pending_and_hide_done(): void
    {
        $client = Client::factory()
                        ->create();

        $highPending = Task::factory()
                            ->for($client)
                            ->create([
                                'priority' => TaskPriority::High,
                                'status' => TaskStatus::Pending,
                            ]);

        $lowDone = Task::factory()
                        ->for($client)
                        ->done()
                        ->create([
                            'priority' => TaskPriority::Low,
                        ]);

        $priorityResults = $this->service->paginateForIndex(null, TaskPriority::High->value, false, false);
        $pendingResults = $this->service->paginateForIndex(null, null, true, false);
        $includingDoneResults = $this->service->paginateForIndex(null, null, false, false);

        $priorityResultIds = $priorityResults->pluck('id');

        $this->assertTrue($priorityResultIds->contains($highPending->id));
        $this->assertFalse($priorityResultIds->contains($lowDone->id));

        $pendingResultIds = $pendingResults->pluck('id');

        $this->assertTrue($pendingResultIds->contains($highPending->id));
        $this->assertFalse($pendingResultIds->contains($lowDone->id));

        $includingDoneResultIds = $includingDoneResults->pluck('id');

        $this->assertTrue($includingDoneResultIds->contains($lowDone->id));
    }

    public function test_paginate_for_index_hides_done_tasks_by_default(): void
    {
        $client = Client::factory()
                        ->create();

        $pending = Task::factory()
                        ->for($client)
                        ->create();

        $done = Task::factory()
                    ->for($client)
                    ->done()
                    ->create();

        $results = $this->service->paginateForIndex(null, null, false, true);

        $resultIds = $results->pluck('id');

        $this->assertTrue($resultIds->contains($pending->id));
        $this->assertFalse($resultIds->contains($done->id));
    }

    public function test_update_persists_changes_and_dispatches_event(): void
    {
        Event::fake([TaskUpdated::class]);

        $client = Client::factory()
                        ->create();
        $task = Task::factory()
                    ->for($client)
                    ->create(['title' => 'Before update']);

        $result = $this->service->update($task, [
                            'title' => 'After update',
                            'priority' => TaskPriority::High->value,
        ]);

        $this->assertSame('After update', $result->title);
        $this->assertSame(TaskPriority::High, $result->priority);

        Event::assertDispatched(TaskUpdated::class);
    }

    public function test_update_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()
                        ->create();

        $otherClient = Client::factory()
                    ->create();
        $task = Task::factory()
                    ->for($client)
                    ->create();

        $opportunity = Opportunity::factory()
                            ->for($otherClient)
                            ->create();

        $this->expectException(ValidationException::class);

        $this->service->update($task, [
                            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_delete_removes_task(): void
    {
        $task = Task::factory()
                    ->create();

        $this->service->delete($task);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_create_allows_missing_or_null_opportunity_without_validation_error(): void
    {
        Event::fake([TaskCreated::class]);

        $client = Client::factory()
                        ->create();
        $withoutOpportunityDueAt = Carbon::now()
                                        ->addDay();
        $withNullOpportunityDueAt = Carbon::now()
                                        ->addDay();

        $withoutOpportunityKey = $this->service->create([
                            'client_id' => $client->id,
                            'title' => 'No opportunity key',
                            'due_at' => $withoutOpportunityDueAt,
                            'priority' => TaskPriority::Medium->value,
        ]);

        $withNullOpportunity = $this->service->create([
                            'client_id' => $client->id,
                            'opportunity_id' => null,
                            'title' => 'Null opportunity',
                            'due_at' => $withNullOpportunityDueAt,
                            'priority' => TaskPriority::Medium->value,
        ]);

        $this->assertNull($withoutOpportunityKey->opportunity_id);
        $this->assertNull($withNullOpportunity->opportunity_id);
    }

    public function test_assert_opportunity_belongs_to_client_ignores_unknown_opportunity_id(): void
    {
        $client = Client::factory()
                        ->create();

        $method = new \ReflectionMethod(TaskService::class, 'assertOpportunityBelongsToClient');
        $method->setAccessible(true);

        $method->invoke($this->service, [
                            'client_id' => $client->id,
                            'opportunity_id' => 999999,
        ]);

        $this->assertTrue(true);
    }
}
