<div class="space-y-6" data-test="follow-ups-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Follow-ups') }}</flux:heading>

        <flux:button
            variant="primary"
            wire:click="openCreateModal"
            data-test="follow-ups-create-button"
        >
            {{ __('New follow-up') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :label="__('Search client')"
            :placeholder="__('Search by company name')"
            data-test="follow-ups-search"
        />

        <flux:select
            wire:model.live="priorityFilter"
            :label="__('Priority')"
            data-test="follow-ups-priority-filter"
        >
            <flux:select.option value="all">{{ __('All priorities') }}</flux:select.option>
            @foreach (\App\Enums\FollowUpPriority::cases() as $priority)
                <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex flex-col justify-end gap-3">
            <flux:checkbox
                wire:model.live="hideCompleted"
                :label="__('Hide completed')"
                data-test="follow-ups-hide-completed"
            />
            <flux:checkbox
                wire:model.live="overdueOnly"
                :label="__('Overdue only')"
                data-test="follow-ups-overdue-filter"
            />
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-border-default bg-surface">
        <table class="w-full text-left text-sm font-light text-text-secondary">
            <thead>
                <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                    <th class="h-10 px-4">{{ __('Due date') }}</th>
                    <th class="h-10 px-4">{{ __('Client') }}</th>
                    <th class="h-10 px-4">{{ __('Opportunity') }}</th>
                    <th class="h-10 px-4">{{ __('Priority') }}</th>
                    <th class="h-10 px-4">{{ __('Status') }}</th>
                    <th class="h-10 px-4 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->followUps as $followUp)
                    <tr
                        wire:key="follow-up-{{ $followUp->id }}"
                        @class([
                            'h-12 border-b border-border-subtle hover:bg-hover',
                            'odd:bg-transparent even:bg-app/40' => ! $followUp->hasCompletedRowHighlight() && ! $followUp->hasOverdueRowHighlight(),
                            'bg-status-success/10' => $followUp->hasCompletedRowHighlight(),
                            'bg-status-danger/15' => $followUp->hasOverdueRowHighlight(),
                            'border-l-2 border-l-status-danger' => $followUp->hasOverdueRowHighlight(),
                        ])
                        data-test="follow-ups-row-{{ $followUp->id }}"
                        @if ($followUp->isOverdue())
                            data-overdue-row="true"
                        @endif
                        @if ($followUp->reminder_status === \App\Enums\FollowUpReminderStatus::Completed)
                            data-completed-row="true"
                        @endif
                    >
                        <td class="px-4">{{ $followUp->due_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 font-medium text-text-primary">{{ $followUp->client->company_name }}</td>
                        <td class="px-4">{{ $followUp->opportunity?->title ?? '—' }}</td>
                        <td class="px-4">{{ $followUp->priority->label() }}</td>
                        <td class="px-4">
                            <span
                                class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $followUp->statusBadgeClasses() }}"
                                data-test="follow-ups-status-badge-{{ $followUp->id }}"
                                data-status="{{ $followUp->reminder_status->value }}"
                            >
                                {{ $followUp->reminder_status->label() }}
                            </span>
                        </td>
                        <td class="px-4 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="ellipsis-horizontal"
                                    data-test="follow-ups-actions-{{ $followUp->id }}"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="openEditModal({{ $followUp->id }})"
                                        data-test="follow-ups-edit-{{ $followUp->id }}"
                                    >
                                        {{ __('Edit') }}
                                    </flux:menu.item>

                                    @if ($followUp->reminder_status === \App\Enums\FollowUpReminderStatus::Pending)
                                        <flux:menu.item
                                            wire:click="markComplete({{ $followUp->id }})"
                                            data-test="follow-ups-complete-{{ $followUp->id }}"
                                        >
                                            {{ __('Mark complete') }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-text-muted" data-test="follow-ups-empty">
                            {{ __('No follow-ups found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->followUps->hasPages())
            <div class="border-t border-border-default px-4 py-3" data-test="follow-ups-pagination">
                <flux:pagination :paginator="$this->followUps" />
            </div>
        @endif
    </div>

    @include('livewire.follow-ups.partials.form-modal')
</div>
