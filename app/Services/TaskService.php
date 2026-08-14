<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public const INDEX_PER_PAGE = 20;

    public const DASHBOARD_PENDING_LIMIT = 10;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Task
    {
        $this->assertOpportunityBelongsToClient($attributes);

        $attributes['status'] = TaskStatus::Pending;
        $attributes['completed_at'] = null;

        $task = Task::create($attributes);

        /**
         * @calls app/Listeners/QueueCalendarEventForTask
         * @calls app/Listeners/EvaluateSlackRulesForTask
         */
        TaskCreated::dispatch($task->fresh(['client', 'opportunity']));

        return $task;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes): Task
    {
        $merged = array_merge($task->only([
            'client_id',
            'opportunity_id',
        ]), $attributes);

        $this->assertOpportunityBelongsToClient($merged);

        $task->update($attributes);

        /**
         * @calls app/Listeners/QueueCalendarEventForTask
         * @calls app/Listeners/EvaluateSlackRulesForTask
         */
        TaskUpdated::dispatch($task->fresh(['client', 'opportunity']));

        return $task;
    }

    public function markDone(Task $task): Task
    {
        $task->status = TaskStatus::Done;
        $task->completed_at = now();
        $task->save();

        /**
         * @calls app/Listeners/QueueCalendarEventForTask
         * @calls app/Listeners/EvaluateSlackRulesForTask
         */
        TaskUpdated::dispatch($task->fresh(['client', 'opportunity']));

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function paginateForIndex(
        ?string $search,
        ?string $priorityFilter,
        bool $pendingOnly,
        bool $hideDone = true,
        int $page = 1,
        int $perPage = self::INDEX_PER_PAGE,
    ): LengthAwarePaginator {
        $query = Task::query()
            ->with(['client', 'opportunity'])
            ->orderBy('due_at');

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $taskQuery) use ($search): void {
                $term = '%'.strtolower($search).'%';
                $taskQuery
                    ->whereRaw('lower(title) like ?', [$term])
                    ->orWhereHas('client', function (Builder $clientQuery) use ($term): void {
                        $clientQuery->whereRaw('lower(company_name) like ?', [$term]);
                    });
            });
        }

        if ($priorityFilter !== null && $priorityFilter !== '' && $priorityFilter !== 'all') {
            $query->where('priority', $priorityFilter);
        }

        if ($pendingOnly) {
            $query->pending();
        }

        if ($hideDone) {
            $query->where('status', '!=', TaskStatus::Done);
        }

        return $query->paginate(
            perPage: $perPage,
            page: $page,
        );
    }

    /**
     * Pending tasks for the operational dashboard (FDR-008).
     *
     * @return Collection<int, Task>
     */
    public function pendingForDashboard(int $limit = self::DASHBOARD_PENDING_LIMIT): Collection
    {
        return Task::query()
            ->pending()
            ->with(['client', 'opportunity'])
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertOpportunityBelongsToClient(array $attributes): void
    {
        if (! array_key_exists('opportunity_id', $attributes)) {
            return;
        }

        if ($attributes['opportunity_id'] === null) {
            return;
        }

        $opportunity = Opportunity::find($attributes['opportunity_id']);

        if ($opportunity === null) {
            return;
        }

        if ((int) $attributes['client_id'] !== (int) $opportunity->client_id) {
            throw ValidationException::withMessages([
                'opportunity_id' => __('The selected opportunity must belong to the same client.'),
            ]);
        }
    }
}
