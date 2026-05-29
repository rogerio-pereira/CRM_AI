<div class="space-y-6" data-test="opportunities-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Opportunities') }}</flux:heading>

        <flux:button
            variant="primary"
            wire:click="openCreateModal"
            data-test="opportunities-create-button"
        >
            {{ __('Add Opportunity') }}
        </flux:button>
    </div>

    <div class="overflow-x-auto pb-4" data-test="kanban-board">
        <div class="flex min-w-max gap-4">
            @foreach ($this->orderedStages as $stage)
                @php($stageOpportunities = $this->opportunitiesByStage[$stage->value] ?? collect())

                <div
                    class="flex min-w-[280px] max-w-[280px] flex-col gap-4 rounded-lg border p-3 transition-colors {{ $stage->columnClasses() }}"
                    data-test="kanban-column-{{ $stage->slug() }}"
                    @if ($stage->requiresUserAction())
                        data-user-action-column="true"
                    @endif
                    x-data="{ draggingOver: false }"
                    @dragover.prevent="draggingOver = true"
                    @dragleave.prevent="draggingOver = false"
                    @drop.prevent="
                        draggingOver = false;
                        const opportunityId = event.dataTransfer.getData('opportunity-id');
                        if (opportunityId) {
                            $wire.moveToStage(Number(opportunityId), '{{ $stage->value }}');
                        }
                    "
                    :class="{
                        'border-border-strong': draggingOver && !{{ $stage->requiresUserAction() ? 'true' : 'false' }},
                        'kanban-column-user-action--dragging': draggingOver && {{ $stage->requiresUserAction() ? 'true' : 'false' }},
                    }"
                >
                    <div class="flex items-center justify-between gap-2">
                        @if ($stage->requiresUserAction())
                            <span class="{{ $stage->columnHeadingClasses() }}">{{ $stage->label() }}</span>
                        @else
                            <flux:subheading class="{{ $stage->columnHeadingClasses() }}">{{ $stage->label() }}</flux:subheading>
                        @endif
                        <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $stage->badgeClasses() }}">
                            {{ $stageOpportunities->count() }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-3">
                        @foreach ($stageOpportunities as $opportunity)
                            <div
                                wire:key="opportunity-card-{{ $opportunity->id }}"
                                draggable="true"
                                @dragstart="event.dataTransfer.setData('opportunity-id', '{{ $opportunity->id }}')"
                                class="cursor-grab rounded-lg border border-border bg-elevated p-3 transition-colors hover:border-border-strong active:cursor-grabbing"
                                data-test="kanban-card-{{ $opportunity->id }}"
                            >
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <button
                                            type="button"
                                            class="text-left text-sm font-medium text-text-primary hover:text-primary"
                                            wire:click="openDetailModal({{ $opportunity->id }})"
                                            data-test="kanban-card-open-{{ $opportunity->id }}"
                                        >
                                            {{ $opportunity->title }}
                                        </button>

                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="ellipsis-horizontal"
                                                data-test="kanban-card-actions-{{ $opportunity->id }}"
                                            />

                                            <flux:menu>
                                                <flux:menu.item
                                                    wire:click="openDetailModal({{ $opportunity->id }})"
                                                    data-test="kanban-card-view-{{ $opportunity->id }}"
                                                >
                                                    {{ __('View details') }}
                                                </flux:menu.item>
                                                <flux:menu.item
                                                    wire:click="openEditModal({{ $opportunity->id }})"
                                                    data-test="kanban-card-edit-{{ $opportunity->id }}"
                                                >
                                                    {{ __('Edit') }}
                                                </flux:menu.item>

                                                @foreach ($this->orderedStages as $moveStage)
                                                    @if ($moveStage !== $opportunity->stage)
                                                        <flux:menu.item
                                                            wire:click="moveToStage({{ $opportunity->id }}, '{{ $moveStage->value }}')"
                                                            data-test="kanban-card-move-{{ $opportunity->id }}-{{ $moveStage->slug() }}"
                                                        >
                                                            {{ __('Move to :stage', ['stage' => $moveStage->label()]) }}
                                                        </flux:menu.item>
                                                    @endif
                                                @endforeach
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>

                                    <flux:text class="text-xs text-text-muted">
                                        {{ $opportunity->client->company_name }}
                                    </flux:text>

                                    <div class="flex items-center justify-between gap-2 text-xs text-text-secondary">
                                        <span data-test="kanban-card-value-{{ $opportunity->id }}">
                                            @if ($opportunity->estimated_value !== null)
                                                {{ __(':value', ['value' => number_format((float) $opportunity->estimated_value, 2)]) }}
                                            @else
                                                {{ __('—') }}
                                            @endif
                                        </span>

                                        <span data-test="kanban-card-follow-up-{{ $opportunity->id }}">
                                            @if ($opportunity->next_follow_up_date)
                                                {{ \Illuminate\Support\Carbon::parse($opportunity->next_follow_up_date)->format('M j') }}
                                            @else
                                                {{ __('—') }}
                                            @endif
                                        </span>
                                    </div>

                                    <div class="flex justify-end border-t border-border-subtle pt-2">
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="calendar-days"
                                            wire:click="$dispatch('open-follow-up-for-opportunity', { opportunityId: {{ $opportunity->id }} })"
                                            data-test="kanban-card-create-follow-up-{{ $opportunity->id }}"
                                        >
                                            {{ __('Follow-up') }}
                                        </flux:button>
                                    </div>

                                    @if ($opportunity->hasAiRecommendations())
                                        <div class="flex items-center gap-1 text-xs text-ai" data-test="kanban-card-ai-{{ $opportunity->id }}">
                                            <flux:icon.sparkles class="size-3.5" />
                                            <span>{{ __('AI insight') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('livewire.opportunities.partials.form-modal')
    @include('livewire.opportunities.partials.detail-modal')

    <livewire:follow-ups.quick-create-modal />
</div>
