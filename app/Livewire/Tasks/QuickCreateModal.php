<?php

namespace App\Livewire\Tasks;

use App\Concerns\TaskValidationRules;
use App\Enums\TaskPriority;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\TaskService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickCreateModal extends Component
{
    use TaskValidationRules;

    public bool $showFormModal = false;

    public ?int $client_id = null;

    public ?int $opportunity_id = null;

    public string $title = '';

    public string $description = '';

    public string $due_at = '';

    public string $priority = 'medium';

    public bool $is_important = false;

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

    #[On('open-task-for-opportunity')]
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

    #[On('open-task-for-client')]
    public function openForClient(int $clientId): void
    {
        Client::query()->findOrFail($clientId);

        $this->resetForm();
        $this->client_id = $clientId;
        $this->opportunity_id = null;
        $this->showFormModal = true;
        unset($this->opportunityOptions);
    }

    public function updatedClientId(): void
    {
        $this->opportunity_id = null;
        unset($this->opportunityOptions);
    }

    public function saveTask(TaskService $taskService): void
    {
        $validated = $this->validate(self::formRules());

        $opportunityId = $validated['opportunity_id'] ?? null;

        if ($opportunityId === '' || $opportunityId === null) {
            $opportunityId = null;
        }

        $taskService->create([
            'client_id' => (int) $validated['client_id'],
            'opportunity_id' => $opportunityId === null ? null : (int) $opportunityId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_at' => $validated['due_at'],
            'priority' => $validated['priority'],
            'is_important' => (bool) ($validated['is_important'] ?? false),
        ]);

        Flux::toast(variant: 'success', text: __('Task created.'));

        $this->showFormModal = false;
        $this->resetForm();
        $this->dispatch('task-created');
    }

    public function render(): View
    {
        return view('livewire.tasks.quick-create-modal');
    }

    private function resetForm(): void
    {
        $this->client_id = null;
        $this->opportunity_id = null;
        $this->title = '';
        $this->description = '';
        $this->due_at = now()->addDay()->format('Y-m-d\TH:i');
        $this->priority = TaskPriority::Medium->value;
        $this->is_important = false;
        $this->resetValidation();
        unset($this->opportunityOptions);
    }
}
