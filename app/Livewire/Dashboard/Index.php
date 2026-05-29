<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardMetricsService;
use App\Services\DashboardTablesService;
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

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }
}
