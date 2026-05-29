<?php

namespace App\Livewire\FollowUps;

use App\Concerns\FollowUpValidationRules;
use App\Enums\FollowUpPriority;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\FollowUpService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickCreateModal extends Component
{
    use FollowUpValidationRules;

    public bool $showFormModal = false;

    public ?int $client_id = null;

    public ?int $opportunity_id = null;

    public string $due_at = '';

    public string $priority = 'medium';

    public string $notes = '';

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

    #[On('open-follow-up-for-opportunity')]
    public function openForOpportunity(int $opportunityId): void
    {
        $opportunity = Opportunity::query()
            ->with('client')
            ->findOrFail($opportunityId);

        $this->resetForm();
        $this->client_id = $opportunity->client_id;
        $this->opportunity_id = $opportunity->id;
        $this->showFormModal = true;
        unset($this->opportunityOptions);
    }

    public function updatedClientId(): void
    {
        $this->opportunity_id = null;
        unset($this->opportunityOptions);
    }

    public function saveFollowUp(FollowUpService $followUpService): void
    {
        $validated = $this->validate(self::formRules());

        $opportunityId = $validated['opportunity_id'] ?? null;

        if ($opportunityId === '' || $opportunityId === null) {
            $opportunityId = null;
        }

        $followUpService->create([
            'client_id' => (int) $validated['client_id'],
            'opportunity_id' => $opportunityId === null ? null : (int) $opportunityId,
            'due_at' => $validated['due_at'],
            'priority' => $validated['priority'],
            'notes' => $validated['notes'] ?? null,
        ]);

        Flux::toast(variant: 'success', text: __('Follow-up created.'));

        $this->showFormModal = false;
        $this->resetForm();
        $this->dispatch('follow-up-created');
    }

    public function render(): View
    {
        return view('livewire.follow-ups.quick-create-modal');
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
