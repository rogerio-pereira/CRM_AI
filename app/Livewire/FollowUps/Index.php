<?php

namespace App\Livewire\FollowUps;

use App\Concerns\FollowUpValidationRules;
use App\Enums\FollowUpPriority;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Services\FollowUpService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Follow-ups')]
class Index extends Component
{
    use FollowUpValidationRules;
    use WithPagination;

    public string $search = '';

    public string $priorityFilter = 'all';

    public bool $overdueOnly = false;

    public bool $showFormModal = false;

    public ?int $editingFollowUpId = null;

    public ?int $client_id = null;

    public ?int $opportunity_id = null;

    public string $due_at = '';

    public string $priority = 'medium';

    public string $notes = '';

    public function getFollowUpsProperty(): LengthAwarePaginator
    {
        return app(FollowUpService::class)->paginateForIndex(
            $this->search !== '' ? $this->search : null,
            $this->priorityFilter !== 'all' ? $this->priorityFilter : null,
            $this->overdueOnly,
            page: $this->getPage(),
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOverdueOnly(): void
    {
        $this->resetPage();
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clientOptions(): Collection
    {
        return Client::orderBy('company_name')->get();
    }

    /**
     * @return Collection<int, Opportunity>
     */
    #[Computed]
    public function opportunityOptions(): Collection
    {
        if ($this->client_id === null) {
            return collect();
        }

        return Opportunity::query()
            ->where('client_id', $this->client_id)
            ->orderBy('title')
            ->get();
    }

    public function updatedClientId(): void
    {
        $this->opportunity_id = null;
        unset($this->opportunityOptions);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingFollowUpId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $followUpId): void
    {
        $followUp = FollowUp::findOrFail($followUpId);

        $this->editingFollowUpId = $followUp->id;
        $this->client_id = $followUp->client_id;
        $this->opportunity_id = $followUp->opportunity_id;
        $this->due_at = $followUp->due_at->format('Y-m-d\TH:i');
        $this->priority = $followUp->priority->value;
        $this->notes = $followUp->notes ?? '';
        $this->showFormModal = true;
        unset($this->opportunityOptions);
    }

    public function saveFollowUp(FollowUpService $followUpService): void
    {
        $validated = $this->validate(self::formRules());

        $opportunityId = $validated['opportunity_id'] ?? null;

        if ($opportunityId === '' || $opportunityId === null) {
            $opportunityId = null;
        }

        $attributes = [
            'client_id' => (int) $validated['client_id'],
            'opportunity_id' => $opportunityId === null ? null : (int) $opportunityId,
            'due_at' => $validated['due_at'],
            'priority' => $validated['priority'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($this->editingFollowUpId === null) {
            $followUpService->create($attributes);
            Flux::toast(variant: 'success', text: __('Follow-up created.'));
        } else {
            $followUp = FollowUp::findOrFail($this->editingFollowUpId);
            $followUpService->update($followUp, $attributes);
            Flux::toast(variant: 'success', text: __('Follow-up updated.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function markComplete(int $followUpId, FollowUpService $followUpService): void
    {
        $followUp = FollowUp::findOrFail($followUpId);
        $followUpService->markComplete($followUp);
        Flux::toast(variant: 'success', text: __('Follow-up completed.'));
    }

    public function snooze(int $followUpId, FollowUpService $followUpService): void
    {
        $followUp = FollowUp::findOrFail($followUpId);
        $followUpService->snooze($followUp);
        Flux::toast(variant: 'success', text: __('Follow-up snoozed.'));
    }

    public function render(): View
    {
        return view('livewire.follow-ups.index');
    }

    private function resetForm(): void
    {
        $this->client_id = null;
        $this->opportunity_id = null;
        $this->due_at = now()->addDay()->format('Y-m-d\TH:i');
        $this->priority = FollowUpPriority::Medium->value;
        $this->notes = '';
        $this->resetValidation();
        unset($this->opportunityOptions);
    }
}
