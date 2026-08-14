<?php

namespace App\Services;

use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Operational dashboard metrics (ADR-014).
 *
 * Sales per day uses SUM(estimated_value) for opportunities in Won stage,
 * grouped by DATE(updated_at) as a proxy for the day the deal was won (no won_at).
 */
class DashboardMetricsService
{
    private const SERIES_DAYS = 30;

    public function leadsCreatedToday(): int
    {
        return Client::query()
            ->whereDate('created_at', today())
            ->count();
    }

    public function opportunitiesCreatedToday(): int
    {
        return Opportunity::query()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * @return list<array{date: string, value: int}>
     */
    public function leadsPerDayLast30Days(): array
    {
        $aggregated = $this->aggregateDailyCounts(
            Client::query(),
            'created_at',
        );

        return $this->fillDailySeries($aggregated);
    }

    /**
     * @return list<array{date: string, value: int}>
     */
    public function opportunitiesPerDayLast30Days(): array
    {
        $aggregated = $this->aggregateDailyCounts(
            Opportunity::query(),
            'created_at',
        );

        return $this->fillDailySeries($aggregated);
    }

    /**
     * @return list<array{date: string, value: float}>
     */
    public function salesPerDayLast30Days(): array
    {
        $start = $this->seriesStart();

        $rows = Opportunity::query()
            ->where('stage', PipelineStage::Won)
            ->where('updated_at', '>=', $start)
            ->selectRaw('DATE(updated_at) as day, COALESCE(SUM(estimated_value), 0) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $aggregated = $rows->mapWithKeys(function (Opportunity $row): array {
            $day = Carbon::parse($row->day)->toDateString();

            return [$day => (float) $row->total];
        });

        return $this->fillDailySeries($aggregated, isFloat: true);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Collection<string, int>
     */
    private function aggregateDailyCounts(Builder $query, string $column): Collection
    {
        $start = $this->seriesStart();

        $rows = $query
            ->where($column, '>=', $start)
            ->selectRaw("DATE({$column}) as day, COUNT(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return $rows->mapWithKeys(function (Model $row): array {
            $day = Carbon::parse($row->day)->toDateString();

            return [$day => (int) $row->total];
        });
    }

    private function seriesStart(): Carbon
    {
        return Carbon::now()
                    ->startOfDay()
                    ->subDays(self::SERIES_DAYS - 1);
    }

    /**
     * @param  Collection<string, int|float>  $aggregated
     * @return list<array{date: string, value: int|float}>
     */
    private function fillDailySeries(Collection $aggregated, bool $isFloat = false): array
    {
        $start = $this->seriesStart();
        $series = [];

        for ($offset = 0; $offset < self::SERIES_DAYS; $offset++) {
            $date = $start->copy()
                        ->addDays($offset)
                        ->toDateString();

            if ($isFloat) {
                $defaultValue = 0.0;
            } else {
                $defaultValue = 0;
            }

            $value = $aggregated->get($date, $defaultValue);

            if ($isFloat) {
                $series[] = [
                    'date' => $date,
                    'value' => (float) $value,
                ];
            } else {
                $series[] = [
                    'date' => $date,
                    'value' => (int) $value,
                ];
            }
        }

        return $series;
    }
}
