<?php

namespace App\Services;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Models\Opportunity;
use Illuminate\Support\Collection;
use RuntimeException;

class OpportunityService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Opportunity
    {
        $attributes['stage'] = PipelineStage::Lead;
        $attributes['status'] = OpportunityStatus::Open;

        $opportunity = Opportunity::create($attributes)
                            ->fresh(['client']);

        if ($opportunity === null) {
            throw new RuntimeException('Created opportunity could not be reloaded.');
        }

        /**
         * @calls app/Listeners/DispatchAiOnOpportunityCreated
         */
        OpportunityCreated::dispatch($opportunity);

        return $opportunity;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Opportunity $opportunity, array $attributes): Opportunity
    {
        $opportunity->update($attributes);

        return $opportunity->fresh(['client']);
    }

    public function moveToStage(
        Opportunity $opportunity,
        PipelineStage $targetStage,
        ?int $userId = null,
    ): Opportunity {
        $fromStage = $opportunity->stage;

        if ($fromStage === $targetStage) {
            return $opportunity;
        }

        $opportunity->stage = $targetStage;

        if ($targetStage === PipelineStage::Won) {
            $opportunity->status = OpportunityStatus::Won;
        } elseif ($targetStage === PipelineStage::Lost) {
            $opportunity->status = OpportunityStatus::Lost;
        } else {
            $opportunity->status = OpportunityStatus::Open;
        }

        $opportunity->save();

        $freshOpportunity = $opportunity->fresh(['client']);

        if ($freshOpportunity === null) {
            throw new RuntimeException('Updated opportunity could not be reloaded.');
        }

        /**
         * @calls app/Listeners/DispatchAiOnOpportunityStageChanged
         */
        OpportunityStageChanged::dispatch(
            $freshOpportunity,
            $fromStage,
            $targetStage,
            $userId,
        );

        return $opportunity;
    }

    /**
     * @return array<string, Collection<int, Opportunity>>
     */
    public function groupedByStage(): array
    {
        $opportunities = Opportunity::query()
            ->with(['client'])
            ->withNextFollowUpDate()
            ->orderByDesc('updated_at')
            ->get();

        $grouped = [];

        foreach (PipelineStage::ordered() as $stage) {
            $grouped[$stage->value] = $opportunities
                ->where('stage', $stage)
                ->values();
        }

        return $grouped;
    }
}
