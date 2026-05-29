<div class="space-y-8" data-test="dashboard-page">
    <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Dashboard') }}</flux:heading>

    <section class="grid gap-4 sm:grid-cols-2" data-test="dashboard-metrics">
        <div
            class="rounded-lg border border-border-default bg-surface p-6"
            data-test="dashboard-metric-leads-today"
        >
            <flux:text class="text-text-muted">{{ __('Leads created today') }}</flux:text>
            <flux:heading size="2xl" class="mt-2 font-bold text-text-primary">
                {{ $this->leadsCreatedToday }}
            </flux:heading>
        </div>

        <div
            class="rounded-lg border border-border-default bg-surface p-6"
            data-test="dashboard-metric-opportunities-today"
        >
            <flux:text class="text-text-muted">{{ __('Opportunities created today') }}</flux:text>
            <flux:heading size="2xl" class="mt-2 font-bold text-text-primary">
                {{ $this->opportunitiesCreatedToday }}
            </flux:heading>
        </div>
    </section>

    <section class="space-y-4" data-test="dashboard-charts">
        <flux:heading size="lg" class="text-text-secondary">{{ __('Last 30 days') }}</flux:heading>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-border-subtle bg-app/30 p-4">
                <flux:text class="mb-3 text-xs font-medium uppercase text-text-muted">
                    {{ __('Leads per day') }}
                </flux:text>
                @include('livewire.dashboard.partials.bar-chart', [
                    'series' => $this->leadsSeries,
                    'testPrefix' => 'leads',
                ])
            </div>

            <div class="rounded-lg border border-border-subtle bg-app/30 p-4">
                <flux:text class="mb-3 text-xs font-medium uppercase text-text-muted">
                    {{ __('Opportunities per day') }}
                </flux:text>
                @include('livewire.dashboard.partials.bar-chart', [
                    'series' => $this->opportunitiesSeries,
                    'testPrefix' => 'opportunities',
                ])
            </div>

            <div class="rounded-lg border border-border-subtle bg-app/30 p-4">
                <flux:text class="mb-3 text-xs font-medium uppercase text-text-muted">
                    {{ __('Sales per day') }}
                </flux:text>
                @include('livewire.dashboard.partials.bar-chart', [
                    'series' => $this->salesSeries,
                    'testPrefix' => 'sales',
                ])
            </div>
        </div>
    </section>

    <section class="space-y-4" data-test="dashboard-tables">
        <div
            class="overflow-hidden rounded-lg border border-border-default bg-surface"
            data-test="dashboard-table-tasks"
        >
            <div class="border-b border-border-default px-4 py-3">
                <flux:heading size="md" class="font-semibold text-text-primary">
                    {{ __('Pending tasks') }}
                </flux:heading>
            </div>

            <table class="w-full text-left text-sm font-light text-text-secondary">
                <thead>
                    <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                        <th class="h-10 px-4">{{ __('Title') }}</th>
                        <th class="h-10 px-4">{{ __('Client') }}</th>
                        <th class="h-10 px-4">{{ __('Due date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pendingTasks as $task)
                        <tr
                            wire:key="dashboard-task-{{ $task->id }}"
                            class="h-12 border-b border-border-subtle hover:bg-hover"
                            data-test="dashboard-task-row-{{ $task->id }}"
                        >
                            <td class="px-4 font-medium text-text-primary">{{ $task->title }}</td>
                            <td class="px-4">{{ $task->client->company_name }}</td>
                            <td class="px-4">{{ $task->due_at->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-text-muted" data-test="dashboard-tasks-empty">
                                {{ __('No pending tasks.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="overflow-hidden rounded-lg border border-border-default bg-surface"
            data-test="dashboard-table-follow-ups"
        >
            <div class="border-b border-border-default px-4 py-3">
                <flux:heading size="md" class="font-semibold text-text-primary">
                    {{ __('Follow-ups') }}
                </flux:heading>
            </div>

            <table class="w-full text-left text-sm font-light text-text-secondary">
                <thead>
                    <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                        <th class="h-10 px-4">{{ __('Client') }}</th>
                        <th class="h-10 px-4">{{ __('Due date') }}</th>
                        <th class="h-10 px-4">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->actionableFollowUps as $followUp)
                        <tr
                            wire:key="dashboard-follow-up-{{ $followUp->id }}"
                            @class([
                                'h-12 border-b border-border-subtle hover:bg-hover',
                                'bg-status-danger/15' => $followUp->isOverdue(),
                            ])
                            data-test="dashboard-follow-up-row-{{ $followUp->id }}"
                        >
                            <td class="px-4 font-medium text-text-primary">{{ $followUp->client->company_name }}</td>
                            <td class="px-4">{{ $followUp->due_at->format('M j, Y H:i') }}</td>
                            <td class="px-4">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $followUp->statusBadgeClasses() }}">
                                    {{ $followUp->reminder_status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-text-muted" data-test="dashboard-follow-ups-empty">
                                {{ __('No pending follow-ups.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
