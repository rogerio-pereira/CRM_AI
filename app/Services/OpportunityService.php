<?php

namespace App\Services;

use App\Enums\OpportunityStage;
use App\Events\OpportunityStageChanged;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OpportunityService
{
    /**
     * @return Collection<int, Opportunity>
     */
    public function listForKanban(): Collection
    {
        return Opportunity::query()
            ->with('client')
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity
    {
        $opportunity = Opportunity::query()->create([
            'client_id' => $data['client_id'],
            'title' => $data['title'],
            'stage' => $data['stage'] ?? OpportunityStage::Lead,
            'estimated_value' => $data['estimated_value'] ?? null,
            'status' => $data['status'] ?? null,
            'proposal_information' => $data['proposal_information'] ?? null,
            'ai_recommendations' => $data['ai_recommendations'] ?? null,
        ]);

        return $opportunity->load('client');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        $previousStage = $opportunity->stage;

        $opportunity->fill([
            'client_id' => $data['client_id'],
            'title' => $data['title'],
            'estimated_value' => $data['estimated_value'] ?? null,
            'status' => $data['status'] ?? null,
            'proposal_information' => $data['proposal_information'] ?? null,
        ]);

        if (isset($data['stage'])) {
            $opportunity->stage = OpportunityStage::from($data['stage']);
        }

        $opportunity->save();

        $this->dispatchStageChangeIfNeeded($opportunity, $previousStage);

        return $opportunity->load('client');
    }

    public function moveStage(Opportunity $opportunity, OpportunityStage $stage): Opportunity
    {
        $previousStage = $opportunity->stage;

        $opportunity->stage = $stage;
        $opportunity->save();

        $this->dispatchStageChangeIfNeeded($opportunity, $previousStage);

        return $opportunity->load('client');
    }

    private function dispatchStageChangeIfNeeded(Opportunity $opportunity, OpportunityStage $previousStage): void
    {
        if ($previousStage === $opportunity->stage) {
            return;
        }

        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        OpportunityStageChanged::dispatch(
            $opportunity->id,
            $previousStage,
            $opportunity->stage,
            $userId,
        );
    }
}
