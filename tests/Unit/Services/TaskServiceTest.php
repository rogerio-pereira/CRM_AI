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
}
