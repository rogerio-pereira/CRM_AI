<?php

namespace Tests\Unit\Models;

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_overdue_is_false_when_task_is_done(): void
    {
        $dueAt = now()
                        ->subDay();

        $task = Task::factory()
                    ->done()
                    ->create([
                        'due_at' => $dueAt,
                    ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_is_overdue_is_true_when_pending_and_past_due(): void
    {
        $dueAt = now()
                        ->subHour();

        $task = Task::factory()
                    ->create([
                        'due_at' => $dueAt,
                        'status' => TaskStatus::Pending,
                    ]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_status_badge_classes_use_danger_when_overdue(): void
    {
        $dueAt = now()
                        ->subHour();

        $task = Task::factory()
                    ->create([
                        'due_at' => $dueAt,
                        'status' => TaskStatus::Pending,
                    ]);

        $this->assertStringContainsString('status-danger', $task->statusBadgeClasses());
    }

    public function test_status_badge_classes_use_status_when_not_overdue(): void
    {
        $dueAt = now()
                        ->addDay();

        $task = Task::factory()
                    ->create([
                        'due_at' => $dueAt,
                        'status' => TaskStatus::Pending,
                    ]);

        $this->assertSame(
            TaskStatus::Pending->badgeClasses(),
            $task->statusBadgeClasses(),
        );
    }

    public function test_has_done_row_highlight_when_status_is_done(): void
    {
        $task = Task::factory()
                    ->done()
                    ->create();

        $this->assertTrue($task->hasDoneRowHighlight());
        $this->assertFalse($task->hasOverdueRowHighlight());
    }

    public function test_has_overdue_row_highlight_when_pending_and_past_due(): void
    {
        $dueAt = now()
                        ->subHour();

        $task = Task::factory()
                    ->create([
                        'due_at' => $dueAt,
                        'status' => TaskStatus::Pending,
                    ]);

        $this->assertTrue($task->hasOverdueRowHighlight());
    }

    public function test_pending_scope_returns_only_pending_tasks(): void
    {
        $client = Client::factory()
                        ->create();

        $pending = Task::factory()
                        ->for($client)
                        ->create(['status' => TaskStatus::Pending]);

        Task::factory()
                ->for($client)
                ->done()
                ->create();

        $results = Task::query()
                       ->pending()
                       ->get();

        $this->assertCount(1, $results);

        $firstResult = $results->first();

        $this->assertTrue($firstResult->is($pending));
    }

    public function test_opportunity_relationship_returns_related_opportunity(): void
    {
        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create(['title' => 'Linked deal']);

        $task = Task::factory()
                    ->for($client)
                    ->for($opportunity)
                    ->create();
        $relatedOpportunity = $task->opportunity;

        $this->assertTrue($relatedOpportunity->is($opportunity));
        $this->assertSame('Linked deal', $relatedOpportunity->title);
    }
}
