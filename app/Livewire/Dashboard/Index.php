<?php

namespace App\Livewire\Dashboard;

use App\Models\FollowUp;
use App\Models\Task;
use App\Services\DashboardMetricsService;
use App\Services\DashboardTablesService;
use App\Services\FollowUpService;
use App\Services\TaskService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Index extends Component
{
    #[Computed]
    public function leadsCreatedToday(): int
    {
        return app(DashboardMetricsService::class)->leadsCreatedToday();
    }

    #[Computed]
    public function opportunitiesCreatedToday(): int
    {
        return app(DashboardMetricsService::class)->opportunitiesCreatedToday();
    }

    /**
     * @return list<array{date: string, value: int}>
     */
    #[Computed]
    public function leadsSeries(): array
    {
        return app(DashboardMetricsService::class)->leadsPerDayLast30Days();
    }

    /**
     * @return list<array{date: string, value: int}>
     */
    #[Computed]
    public function opportunitiesSeries(): array
    {
        return app(DashboardMetricsService::class)->opportunitiesPerDayLast30Days();
    }

    /**
     * @return list<array{date: string, value: float}>
     */
    #[Computed]
    public function salesSeries(): array
    {
        return app(DashboardMetricsService::class)->salesPerDayLast30Days();
    }

    #[Computed]
    public function pendingTasks(): Collection
    {
        return app(DashboardTablesService::class)->pendingTasks();
    }

    #[Computed]
    public function actionableFollowUps(): Collection
    {
        return app(DashboardTablesService::class)->actionableFollowUps();
    }

    #[Computed]
    public function pendingTasksOverflow(): int
    {
        return app(DashboardTablesService::class)->pendingTasksOverflow();
    }

    #[Computed]
    public function actionableFollowUpsOverflow(): int
    {
        return app(DashboardTablesService::class)->actionableFollowUpsOverflow();
    }

    public function markDone(int $taskId, TaskService $taskService): void
    {
        $task = Task::findOrFail($taskId);
        $taskService->markDone($task);

        unset($this->pendingTasks, $this->pendingTasksOverflow);

        Flux::toast(variant: 'success', text: __('Task completed.'));
    }

    public function markComplete(int $followUpId, FollowUpService $followUpService): void
    {
        $followUp = FollowUp::findOrFail($followUpId);
        $followUpService->markComplete($followUp);

        unset($this->actionableFollowUps, $this->actionableFollowUpsOverflow);

        Flux::toast(variant: 'success', text: __('Follow-up completed.'));
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }
}
