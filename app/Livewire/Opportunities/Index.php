<?php

namespace App\Livewire\Opportunities;

use App\Concerns\OpportunityValidationRules;
use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Opportunities')]
class Index extends Component
{
    use OpportunityValidationRules;

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public ?int $editingOpportunityId = null;

    public ?int $detailOpportunityId = null;

    public string $title = '';

    public ?int $client_id = null;

    public string $estimated_value = '';

    /**
     * @return array<string, Collection<int, Opportunity>>
     */
    #[Computed]
    public function opportunitiesByStage(): array
    {
        return app(OpportunityService::class)->groupedByStage();
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clientOptions(): Collection
    {
        return Client::orderBy('company_name')->get();
    }

    #[Computed]
    public function detailOpportunity(): ?Opportunity
    {
        if ($this->detailOpportunityId === null) {
            return null;
        }

        return Opportunity::with('client')->find($this->detailOpportunityId);
    }

    /**
     * @return list<PipelineStage>
     */
    #[Computed]
    public function orderedStages(): array
    {
        return PipelineStage::ordered();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingOpportunityId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $opportunityId): void
    {
        $opportunity = Opportunity::findOrFail($opportunityId);

        $this->editingOpportunityId = $opportunity->id;
        $this->title = $opportunity->title;
        $this->client_id = $opportunity->client_id;

        if ($opportunity->estimated_value === null) {
            $this->estimated_value = '';
        } else {
            $this->estimated_value = (string) $opportunity->estimated_value;
        }

        $this->showFormModal = true;
    }

    public function openDetailModal(int $opportunityId): void
    {
        $this->detailOpportunityId = $opportunityId;
        $this->showDetailModal = true;
        unset($this->detailOpportunity);
    }

    public function saveOpportunity(OpportunityService $opportunityService): void
    {
        $validated = $this->validate(self::formRules());

        $attributes = [
            'title' => $validated['title'],
            'client_id' => (int) $validated['client_id'],
            'estimated_value' => $this->normalizedEstimatedValue(),
        ];

        if ($this->editingOpportunityId === null) {
            $opportunityService->create($attributes);
            Flux::toast(variant: 'success', text: __('Opportunity created.'));
        } else {
            $opportunity = Opportunity::findOrFail($this->editingOpportunityId);
            $opportunityService->update($opportunity, $attributes);
            Flux::toast(variant: 'success', text: __('Opportunity updated.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->opportunitiesByStage);
    }

    #[On('follow-up-created')]
    public function refreshKanbanAfterFollowUp(): void
    {
        unset($this->opportunitiesByStage);
    }

    #[On('task-created')]
    public function refreshKanbanAfterTask(): void
    {
        unset($this->opportunitiesByStage);
    }

    public function moveToStage(int $opportunityId, string $targetStageValue, OpportunityService $opportunityService): void
    {
        $opportunity = Opportunity::findOrFail($opportunityId);
        $targetStage = PipelineStage::from($targetStageValue);

        $opportunityService->moveToStage(
            $opportunity,
            $targetStage,
            auth()->id(),
        );

        Flux::toast(variant: 'success', text: __('Opportunity moved to :stage.', [
            'stage' => $targetStage->label(),
        ]));

        unset($this->opportunitiesByStage, $this->detailOpportunity);
    }

    public function render(): View
    {
        return view('livewire.opportunities.index');
    }

    private function resetForm(): void
    {
        $this->title = '';
        $this->client_id = null;
        $this->estimated_value = '';
        $this->resetValidation();
    }

    private function normalizedEstimatedValue(): ?string
    {
        $value = trim($this->estimated_value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
