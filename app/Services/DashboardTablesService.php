<?php

namespace App\Services;

use App\Enums\FollowUpReminderStatus;
use App\Models\FollowUp;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardTablesService
{
    public const TABLE_LIMIT = 10;

    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    /**
     * @return Collection<int, Task>
     */
    public function pendingTasks(): Collection
    {
        return $this->taskService->pendingForDashboard(self::TABLE_LIMIT);
    }

    public function pendingTasksTotal(): int
    {
        return Task::query()->pending()->count();
    }

    public function pendingTasksOverflow(): int
    {
        $total = $this->pendingTasksTotal();

        if ($total <= self::TABLE_LIMIT) {
            return 0;
        }

        return $total - self::TABLE_LIMIT;
    }

    /**
     * Pending follow-ups for the dashboard (overdue first by due date).
     *
     * @return Collection<int, FollowUp>
     */
    public function actionableFollowUps(): Collection
    {
        $now = Carbon::now();

        return FollowUp::query()
            ->with(['client', 'opportunity'])
            ->where('reminder_status', FollowUpReminderStatus::Pending)
            ->orderByRaw('CASE WHEN due_at < ? THEN 0 ELSE 1 END', [$now])
            ->orderBy('due_at')
            ->limit(self::TABLE_LIMIT)
            ->get();
    }

    public function actionableFollowUpsTotal(): int
    {
        return FollowUp::query()
            ->where('reminder_status', FollowUpReminderStatus::Pending)
            ->count();
    }

    public function actionableFollowUpsOverflow(): int
    {
        $total = $this->actionableFollowUpsTotal();

        if ($total <= self::TABLE_LIMIT) {
            return 0;
        }

        return $total - self::TABLE_LIMIT;
    }
}
