<div class="space-y-6" data-test="tasks-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Tasks') }}</flux:heading>

        <flux:button
            variant="primary"
            wire:click="openCreateModal"
            data-test="tasks-create-button"
        >
            {{ __('New task') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            :placeholder="__('Search by title or client')"
            data-test="tasks-search"
        />

        <flux:select
            wire:model.live="priorityFilter"
            :label="__('Priority')"
            data-test="tasks-priority-filter"
        >
            <flux:select.option value="all">{{ __('All priorities') }}</flux:select.option>
            @foreach (\App\Enums\TaskPriority::cases() as $priority)
                <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex flex-col justify-end gap-3">
            <flux:checkbox
                wire:model.live="hideDone"
                :label="__('Hide completed')"
                data-test="tasks-hide-done"
            />
            <flux:checkbox
                wire:model.live="pendingOnly"
                :label="__('Pending only')"
                data-test="tasks-pending-filter"
            />
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-border-default bg-surface">
        <table class="w-full text-left text-sm font-light text-text-secondary">
            <thead>
                <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                    <th class="h-10 px-4 w-8"></th>
                    <th class="h-10 px-4">{{ __('Title') }}</th>
                    <th class="h-10 px-4">{{ __('Client') }}</th>
                    <th class="h-10 px-4">{{ __('Opportunity') }}</th>
                    <th class="h-10 px-4">{{ __('Due date') }}</th>
                    <th class="h-10 px-4">{{ __('Priority') }}</th>
                    <th class="h-10 px-4">{{ __('Status') }}</th>
                    <th class="h-10 px-4 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->tasks as $task)
                    <tr
                        wire:key="task-{{ $task->id }}"
                        @class([
                            'h-12 border-b border-border-subtle hover:bg-hover',
                            'odd:bg-transparent even:bg-app/40' => ! $task->hasDoneRowHighlight() && ! $task->hasOverdueRowHighlight(),
                            'bg-status-success/10' => $task->hasDoneRowHighlight(),
                            'bg-status-danger/15' => $task->hasOverdueRowHighlight(),
                            'border-l-2 border-l-status-danger' => $task->hasOverdueRowHighlight(),
                        ])
                        data-test="tasks-row-{{ $task->id }}"
                        @if ($task->isOverdue())
                            data-overdue-row="true"
                        @endif
                        @if ($task->status === \App\Enums\TaskStatus::Done)
                            data-done-row="true"
                        @endif
                    >
                        <td class="px-4">
                            @if ($task->is_important)
                                <flux:icon.star
                                    variant="solid"
                                    class="size-4 text-status-warning"
                                    data-test="tasks-important-icon-{{ $task->id }}"
                                />
                            @endif
                        </td>
                        <td class="px-4 font-medium text-text-primary">{{ $task->title }}</td>
                        <td class="px-4">{{ $task->client->company_name }}</td>
                        <td class="px-4">{{ $task->opportunity?->title ?? '—' }}</td>
                        <td class="px-4">{{ $task->due_at->format('M j, Y H:i') }}</td>
                        <td class="px-4">{{ $task->priority->label() }}</td>
                        <td class="px-4">
                            <span
                                class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $task->statusBadgeClasses() }}"
                                data-test="tasks-status-badge-{{ $task->id }}"
                                data-status="{{ $task->status->value }}"
                            >
                                {{ $task->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="ellipsis-horizontal"
                                    data-test="tasks-actions-{{ $task->id }}"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="openEditModal({{ $task->id }})"
                                        data-test="tasks-edit-{{ $task->id }}"
                                    >
                                        {{ __('Edit') }}
                                    </flux:menu.item>

                                    @if ($task->status === \App\Enums\TaskStatus::Pending)
                                        <flux:menu.item
                                            wire:click="markDone({{ $task->id }})"
                                            data-test="tasks-complete-{{ $task->id }}"
                                        >
                                            {{ __('Mark done') }}
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.item
                                        wire:click="openDeleteModal({{ $task->id }})"
                                        data-test="tasks-delete-{{ $task->id }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-text-muted" data-test="tasks-empty">
                            {{ __('No tasks found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->tasks->hasPages())
            <div class="border-t border-border-default px-4 py-3" data-test="tasks-pagination">
                <flux:pagination :paginator="$this->tasks" />
            </div>
        @endif
    </div>

    @include('livewire.tasks.partials.form-modal')
    @include('livewire.tasks.partials.delete-modal')
</div>
