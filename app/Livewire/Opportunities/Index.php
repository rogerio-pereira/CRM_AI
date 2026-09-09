<?php

namespace App\Livewire\Opportunities;

use App\Concerns\OpportunityNoteValidationRules;
use App\Concerns\OpportunityValidationRules;
use App\Enums\AgentType;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Services\AiOrchestrationService;
use App\Services\OpportunityService;
use App\Support\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Opportunities')]
class Index extends Component
{
    use OpportunityNoteValidationRules;
    use OpportunityValidationRules;

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public ?int $editingOpportunityId = null;

    public ?int $detailOpportunityId = null;

    public string $title = '';

    public ?int $client_id = null;

    public string $estimated_value = '';

    public string $body = '';

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

        return Opportunity::with(['client', 'notes.user'])
                    ->find($this->detailOpportunityId);
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
        $this->body = '';
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
            Toast::show(variant: 'success', text: __('Opportunity created.'));
        } else {
            $opportunity = Opportunity::findOrFail($this->editingOpportunityId);
            $opportunityService->update($opportunity, $attributes);
            Toast::show(variant: 'success', text: __('Opportunity updated.'));
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

    #[On('opportunity-ai-updated')]
    public function refreshDetailAfterAiUpdate(): void
    {
        unset($this->opportunitiesByStage, $this->detailOpportunity);
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

        Toast::show(variant: 'success', text: __('Opportunity moved to :stage.', [
            'stage' => $targetStage->label(),
        ]));

        unset($this->opportunitiesByStage, $this->detailOpportunity);
    }

    public function requalifyOpportunity(int $opportunityId): void
    {
        $opportunity = Opportunity::findOrFail($opportunityId);

        if ($opportunity->qualification_status !== QualificationStatus::Failed) {
            Toast::show(
                variant: 'danger',
                text: __('Only failed qualifications can be retried.'),
            );

            return;
        }

        $opportunityService = app(OpportunityService::class);
        $resetAttributes = [
                'qualification_status' => QualificationStatus::Pending,
                'qualification_last_error' => null,
            ];
        $updatedOpportunity = $opportunityService->update($opportunity, $resetAttributes);

        $userId = auth()->id();
        $payload = [
                'trigger' => 'manual_requalify',
                'opportunity_id' => $updatedOpportunity->id,
                'client_id' => $updatedOpportunity->client_id,
                'user_id' => $userId,
            ];
        $orchestration = app(AiOrchestrationService::class);
        $orchestration->dispatch(AgentType::Qualification, $payload);

        Toast::show(
            variant: 'success',
            text: __('Qualification queued.'),
        );

        unset($this->opportunitiesByStage, $this->detailOpportunity);
    }

    public function addNote(): void
    {
        if ($this->detailOpportunityId === null) {
            return;
        }

        $this->body = trim($this->body);
        $validated = $this->validate(self::noteRules());
        $opportunity = Opportunity::findOrFail($this->detailOpportunityId);
        $attributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ];

        OpportunityNote::create($attributes);

        $this->body = '';
        unset($this->detailOpportunity);

        Toast::show(
            variant: 'success',
            text: __('Note added.'),
        );
    }

    public function deleteNote(int $noteId): void
    {
        if ($this->detailOpportunityId === null) {
            return;
        }

        $note = OpportunityNote::where('opportunity_id', $this->detailOpportunityId)
                            ->findOrFail($noteId);
        $note->delete();
        unset($this->detailOpportunity);

        Toast::show(
            variant: 'success',
            text: __('Note deleted.'),
        );
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
