<div data-test="dashboard-page">
    <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Dashboard') }}</flux:heading>

    <section class="mt-12 space-y-4" data-test="dashboard-metrics-section">
        <flux:heading size="lg" class="text-text-secondary">{{ __('Daily overview') }}</flux:heading>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-6" data-test="dashboard-metrics">
            @include('livewire.dashboard.partials.metric-card', [
                'label' => __('Leads created today'),
                'value' => $this->leadsCreatedToday,
                'icon' => 'user-plus',
                'test' => 'dashboard-metric-leads-today',
                'accentBorder' => 'border-l-accent',
                'iconBg' => 'bg-gradient-to-br from-accent/30 to-accent/10 text-accent',
                'glowClass' => 'bg-accent/30',
                'ringClass' => 'ring-accent/25',
            ])

            @include('livewire.dashboard.partials.metric-card', [
                'label' => __('Opportunities created today'),
                'value' => $this->opportunitiesCreatedToday,
                'icon' => 'briefcase',
                'test' => 'dashboard-metric-opportunities-today',
                'accentBorder' => 'border-l-primary',
                'iconBg' => 'bg-gradient-to-br from-primary/30 to-primary/10 text-primary-focus',
                'glowClass' => 'bg-primary/25',
                'ringClass' => 'ring-primary/25',
            ])
        </div>
    </section>

    <section class="mt-12 space-y-4" data-test="dashboard-charts">
        <flux:heading size="lg" class="text-text-secondary">{{ __('Last 30 days') }}</flux:heading>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3" data-test="dashboard-charts-grid">
            <div class="overflow-visible rounded-lg border border-border-subtle bg-app/30 p-3">
                <flux:text class="mb-2 text-[10px] font-medium uppercase tracking-wide text-text-muted">
                    {{ __('Leads per day') }}
                </flux:text>
                @include('livewire.dashboard.partials.bar-chart', [
                    'series' => $this->leadsSeries,
                    'testPrefix' => 'leads',
                ])
            </div>

            <div class="overflow-visible rounded-lg border border-border-subtle bg-app/30 p-3">
                <flux:text class="mb-2 text-[10px] font-medium uppercase tracking-wide text-text-muted">
                    {{ __('Opportunities per day') }}
                </flux:text>
                @include('livewire.dashboard.partials.bar-chart', [
                    'series' => $this->opportunitiesSeries,
                    'testPrefix' => 'opportunities',
                ])
            </div>

            <div class="overflow-visible rounded-lg border border-border-subtle bg-app/30 p-3">
                <flux:text class="mb-2 text-[10px] font-medium uppercase tracking-wide text-text-muted">
                    {{ __('Sales per day') }}
                </flux:text>
                @include('livewire.dashboard.partials.bar-chart', [
                    'series' => $this->salesSeries,
                    'testPrefix' => 'sales',
                    'valueFormat' => 'decimal',
                ])
            </div>
        </div>
    </section>

    <section class="mt-12 space-y-4" data-test="dashboard-tables-section">
        <flux:heading size="lg" class="text-text-secondary">{{ __('Tasks and follow-ups') }}</flux:heading>

        <div class="grid gap-4 lg:grid-cols-2" data-test="dashboard-tables">
            <div class="overflow-hidden rounded-lg border border-border-default bg-surface">
                <div class="border-b border-border-default px-4 py-3">
                    <flux:heading size="md" class="font-semibold text-text-primary">
                        {{ __('Pending tasks') }}
                    </flux:heading>
                </div>

                @include('livewire.dashboard.partials.tasks-table')
            </div>

            <div class="overflow-hidden rounded-lg border border-border-default bg-surface">
                <div class="border-b border-border-default px-4 py-3">
                    <flux:heading size="md" class="font-semibold text-text-primary">
                        {{ __('Follow-ups') }}
                    </flux:heading>
                </div>

                @include('livewire.dashboard.partials.follow-ups-table')
            </div>
        </div>
    </section>
</div>
