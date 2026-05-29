<div class="overflow-x-auto" data-test="dashboard-table-tasks">
    <table class="w-full min-w-[28rem] text-left text-sm font-light text-text-secondary">
        <thead>
            <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                <th class="h-10 w-8 px-4"></th>
                <th class="h-10 px-4">{{ __('Title') }}</th>
                <th class="h-10 px-4">{{ __('Client') }}</th>
                <th class="h-10 px-4">{{ __('Opportunity') }}</th>
                <th class="h-10 px-4">{{ __('Priority') }}</th>
                <th class="h-10 w-12 px-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->pendingTasks as $task)
                <tr
                    wire:key="dashboard-task-{{ $task->id }}"
                    @class([
                        'h-12 border-b border-border-subtle hover:bg-hover',
                        'odd:bg-transparent even:bg-app/40' => ! $task->hasOverdueRowHighlight(),
                        'bg-status-danger/15' => $task->hasOverdueRowHighlight(),
                        'border-l-2 border-l-status-danger' => $task->hasOverdueRowHighlight(),
                    ])
                    data-test="dashboard-task-row-{{ $task->id }}"
                    @if ($task->isOverdue())
                        data-overdue-row="true"
                    @endif
                >
                    <td class="px-4">
                        @if ($task->is_important)
                            <flux:icon.star
                                variant="solid"
                                class="size-4 text-status-warning"
                                data-test="dashboard-task-important-icon-{{ $task->id }}"
                            />
                        @endif
                    </td>
                    <td class="px-4 font-medium text-text-primary">{{ $task->title }}</td>
                    <td class="px-4">{{ $task->client->company_name }}</td>
                    <td class="px-4">{{ $task->opportunity?->title ?? '—' }}</td>
                    <td class="px-4">
                        <x-priority-badge
                            :priority="$task->priority"
                            data-test="dashboard-task-priority-badge-{{ $task->id }}"
                        />
                    </td>
                    <td class="px-2 text-end">
                        @include('livewire.dashboard.partials.complete-action-button', [
                            'action' => 'markDone('.$task->id.')',
                            'label' => __('Mark done'),
                            'test' => 'dashboard-task-complete-'.$task->id,
                        ])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-text-muted" data-test="dashboard-tasks-empty">
                        {{ __('No pending tasks.') }}
                    </td>
                </tr>
            @endforelse

            @if ($this->pendingTasksOverflow > 0)
                <tr class="border-t border-border-default bg-app/20" data-test="dashboard-tasks-overflow">
                    <td colspan="6" class="px-4 py-3 text-center text-sm">
                        <a
                            href="{{ route('tasks.index') }}"
                            class="font-medium text-accent hover:underline"
                            wire:navigate
                            data-test="dashboard-tasks-overflow-link"
                        >
                            {{ __('+:count items', ['count' => $this->pendingTasksOverflow]) }}
                        </a>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
