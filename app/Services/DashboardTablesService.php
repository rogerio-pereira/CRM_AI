<?php

namespace App\Services;

use App\Enums\FollowUpReminderStatus;
use App\Models\FollowUp;
use App\Models\Task;
use Illuminate\Support\Collection;

class DashboardTablesService
{
    public const FOLLOW_UP_LIMIT = 10;

    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    /**
     * @return Collection<int, Task>
     */
    public function pendingTasks(): Collection
    {
        return $this->taskService->pendingForDashboard();
    }

    /**
     * Pending follow-ups for the dashboard (overdue first by due date).
     *
     * @return Collection<int, FollowUp>
     */
    public function actionableFollowUps(): Collection
    {
        return FollowUp::query()
            ->with(['client', 'opportunity'])
            ->where('reminder_status', FollowUpReminderStatus::Pending)
            ->orderByRaw('CASE WHEN due_at < ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('due_at')
            ->limit(self::FOLLOW_UP_LIMIT)
            ->get();
    }
}
