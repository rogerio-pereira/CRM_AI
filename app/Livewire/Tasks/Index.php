<?php

namespace App\Livewire\Tasks;

use App\Concerns\TaskValidationRules;
use App\Enums\TaskPriority;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use App\Services\TaskService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tasks')]
class Index extends Component
{
    use TaskValidationRules;
    use WithPagination;

    public string $search = '';

    public string $priorityFilter = 'all';

    public bool $pendingOnly = false;

    public bool $hideDone = true;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingTaskId = null;

    public ?int $deleteTaskId = null;

    public ?int $client_id = null;

    public ?int $opportunity_id = null;

    public string $title = '';

    public string $description = '';

    public string $due_at = '';

    public string $priority = 'medium';

    public bool $is_important = false;

    public function getTasksProperty(): LengthAwarePaginator
    {
        return app(TaskService::class)->paginateForIndex(
            $this->search !== '' ? $this->search : null,
            $this->priorityFilter !== 'all' ? $this->priorityFilter : null,
            $this->pendingOnly,
            $this->hideDone,
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

    public function updatedPendingOnly(): void
    {
        $this->resetPage();
    }

    public function updatedHideDone(): void
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

    #[Computed]
    public function deleteTask(): ?Task
    {
        if ($this->deleteTaskId === null) {
            return null;
        }

        return Task::find($this->deleteTaskId);
    }

    public function updatedClientId(): void
    {
        $this->opportunity_id = null;
        unset($this->opportunityOptions);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingTaskId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        $this->editingTaskId = $task->id;
        $this->client_id = $task->client_id;
        $this->opportunity_id = $task->opportunity_id;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->due_at = $task->due_at->format('Y-m-d\TH:i');
        $this->priority = $task->priority->value;
        $this->is_important = $task->is_important;
        $this->showFormModal = true;
        unset($this->opportunityOptions);
    }

    public function openDeleteModal(int $taskId): void
    {
        $this->deleteTaskId = $taskId;
        $this->showDeleteModal = true;
        unset($this->deleteTask);
    }

    public function saveTask(TaskService $taskService): void
    {
        $validated = $this->validate(self::formRules());

        $opportunityId = $validated['opportunity_id'] ?? null;

        if ($opportunityId === '' || $opportunityId === null) {
            $opportunityId = null;
        }

        $attributes = [
            'client_id' => (int) $validated['client_id'],
            'opportunity_id' => $opportunityId === null ? null : (int) $opportunityId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_at' => $validated['due_at'],
            'priority' => $validated['priority'],
            'is_important' => (bool) ($validated['is_important'] ?? false),
        ];

        if ($this->editingTaskId === null) {
            $taskService->create($attributes);
            Flux::toast(variant: 'success', text: __('Task created.'));
        } else {
            $task = Task::findOrFail($this->editingTaskId);
            $taskService->update($task, $attributes);
            Flux::toast(variant: 'success', text: __('Task updated.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function markDone(int $taskId, TaskService $taskService): void
    {
        $task = Task::findOrFail($taskId);
        $taskService->markDone($task);
        Flux::toast(variant: 'success', text: __('Task completed.'));
    }

    public function confirmDelete(TaskService $taskService): void
    {
        if ($this->deleteTaskId === null) {
            return;
        }

        $task = Task::findOrFail($this->deleteTaskId);
        $taskService->delete($task);

        Flux::toast(variant: 'success', text: __('Task deleted.'));
        $this->showDeleteModal = false;
        $this->deleteTaskId = null;
        unset($this->deleteTask);
    }

    public function render(): View
    {
        return view('livewire.tasks.index');
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
