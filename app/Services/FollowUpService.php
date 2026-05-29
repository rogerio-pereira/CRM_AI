<?php

namespace App\Services;

use App\Enums\FollowUpReminderStatus;
use App\Events\FollowUpCreated;
use App\Events\FollowUpUpdated;
use App\Models\FollowUp;
use App\Models\Opportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FollowUpService
{
    public const INDEX_PER_PAGE = 20;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): FollowUp
    {
        $this->assertOpportunityBelongsToClient($attributes);

        $attributes['reminder_status'] = FollowUpReminderStatus::Pending;

        $followUp = FollowUp::create($attributes);

        FollowUpCreated::dispatch($followUp->fresh(['client', 'opportunity']));

        return $followUp;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(FollowUp $followUp, array $attributes): FollowUp
    {
        $merged = array_merge($followUp->only([
            'client_id',
            'opportunity_id',
        ]), $attributes);

        $this->assertOpportunityBelongsToClient($merged);

        $followUp->update($attributes);

        FollowUpUpdated::dispatch($followUp->fresh(['client', 'opportunity']));

        return $followUp;
    }

    public function markComplete(FollowUp $followUp): FollowUp
    {
        $followUp->reminder_status = FollowUpReminderStatus::Completed;
        $followUp->completed_at = now();
        $followUp->snoozed_until = null;
        $followUp->save();

        FollowUpUpdated::dispatch($followUp->fresh(['client', 'opportunity']));

        return $followUp;
    }

    public function snooze(FollowUp $followUp, ?Carbon $until = null): FollowUp
    {
        if ($until === null) {
            $until = now()->addDay();
        }

        $followUp->reminder_status = FollowUpReminderStatus::Snoozed;
        $followUp->snoozed_until = $until;
        $followUp->save();

        FollowUpUpdated::dispatch($followUp->fresh(['client', 'opportunity']));

        return $followUp;
    }

    public function paginateForIndex(
        ?string $search,
        ?string $priorityFilter,
        bool $overdueOnly,
        int $page = 1,
        int $perPage = self::INDEX_PER_PAGE,
    ): LengthAwarePaginator {
        $query = FollowUp::query()
            ->with(['client', 'opportunity'])
            ->orderBy('due_at');

        if ($search !== null && $search !== '') {
            $query->whereHas('client', function ($clientQuery) use ($search): void {
                $clientQuery->whereRaw('lower(company_name) like ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($priorityFilter !== null && $priorityFilter !== '' && $priorityFilter !== 'all') {
            $query->where('priority', $priorityFilter);
        }

        if ($overdueOnly) {
            $query->overdue();
        }

        return $query->paginate(
            perPage: $perPage,
            page: $page,
        );
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
