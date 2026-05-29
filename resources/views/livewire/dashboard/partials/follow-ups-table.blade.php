<div class="overflow-x-auto" data-test="dashboard-table-follow-ups">
    <table class="w-full min-w-[24rem] text-left text-sm font-light text-text-secondary">
        <thead>
            <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                <th class="h-10 px-4">{{ __('Client') }}</th>
                <th class="h-10 px-4">{{ __('Opportunity') }}</th>
                <th class="h-10 px-4">{{ __('Priority') }}</th>
                <th class="h-10 px-4">{{ __('Status') }}</th>
                <th class="h-10 w-12 px-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->actionableFollowUps as $followUp)
                <tr
                    wire:key="dashboard-follow-up-{{ $followUp->id }}"
                    @class([
                        'h-12 border-b border-border-subtle hover:bg-hover',
                        'odd:bg-transparent even:bg-app/40' => ! $followUp->hasOverdueRowHighlight(),
                        'bg-status-danger/15' => $followUp->hasOverdueRowHighlight(),
                        'border-l-2 border-l-status-danger' => $followUp->hasOverdueRowHighlight(),
                    ])
                    data-test="dashboard-follow-up-row-{{ $followUp->id }}"
                    @if ($followUp->isOverdue())
                        data-overdue-row="true"
                    @endif
                >
                    <td class="px-4 font-medium text-text-primary">{{ $followUp->client->company_name }}</td>
                    <td class="px-4">{{ $followUp->opportunity?->title ?? '—' }}</td>
                    <td class="px-4">
                        <x-priority-badge
                            :priority="$followUp->priority"
                            data-test="dashboard-follow-up-priority-badge-{{ $followUp->id }}"
                        />
                    </td>
                    <td class="px-4">
                        <span
                            class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $followUp->statusBadgeClasses() }}"
                            data-test="dashboard-follow-up-status-badge-{{ $followUp->id }}"
                            data-status="{{ $followUp->reminder_status->value }}"
                        >
                            {{ $followUp->reminder_status->label() }}
                        </span>
                    </td>
                    <td class="px-2 text-end">
                        @include('livewire.dashboard.partials.complete-action-button', [
                            'action' => 'markComplete('.$followUp->id.')',
                            'label' => __('Mark complete'),
                            'test' => 'dashboard-follow-up-complete-'.$followUp->id,
                        ])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-text-muted" data-test="dashboard-follow-ups-empty">
                        {{ __('No pending follow-ups.') }}
                    </td>
                </tr>
            @endforelse

            @if ($this->actionableFollowUpsOverflow > 0)
                <tr class="border-t border-border-default bg-app/20" data-test="dashboard-follow-ups-overflow">
                    <td colspan="5" class="px-4 py-3 text-center text-sm">
                        <a
                            href="{{ route('follow-ups.index') }}"
                            class="font-medium text-accent hover:underline"
                            wire:navigate
                            data-test="dashboard-follow-ups-overflow-link"
                        >
                            {{ __('+:count items', ['count' => $this->actionableFollowUpsOverflow]) }}
                        </a>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
