<?php

namespace App\Services;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Events\OpportunityStageChanged;
use App\Models\Opportunity;
use Illuminate\Support\Collection;

class OpportunityService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Opportunity
    {
        $attributes['stage'] = PipelineStage::Lead;
        $attributes['status'] = OpportunityStatus::Open;

        return Opportunity::create($attributes);
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

        OpportunityStageChanged::dispatch(
            $opportunity->fresh(['client']),
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
