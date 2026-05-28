<?php

use App\Concerns\OpportunityValidationRules;
use App\Enums\OpportunityStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Opportunities')] class extends Component {
    use AuthorizesRequests, OpportunityValidationRules;

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public ?int $editingOpportunityId = null;

    public ?int $viewingOpportunityId = null;

    public string $opportunityTitle = '';

    public ?int $client_id = null;

    public string $estimated_value = '';

    public string $stage = '';

    public function mount(): void
    {
        $this->stage = OpportunityStage::Lead->value;
    }

    #[Computed]
    public function kanbanOpportunities()
    {
        return app(OpportunityService::class)->listForKanban();
    }

    #[Computed]
    public function clientOptions()
    {
        return Client::query()->orderBy('company_name')->get();
    }

    #[Computed]
    public function viewingOpportunity(): ?Opportunity
    {
        if ($this->viewingOpportunityId === null) {
            return null;
        }

        return Opportunity::query()->with('client')->find($this->viewingOpportunityId);
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, Opportunity>>
     */
    #[Computed]
    public function opportunitiesByStage(): array
    {
        $grouped = [];

        foreach (OpportunityStage::ordered() as $stage) {
            $grouped[$stage->value] = $this->kanbanOpportunities->where('stage', $stage)->values();
        }

        return $grouped;
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Opportunity::class);
        $this->resetForm();
        $this->editingOpportunityId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $opportunityId): void
    {
        $opportunity = Opportunity::query()->findOrFail($opportunityId);

        $this->authorize('update', $opportunity);

        $this->fillFormFromOpportunity($opportunity);
        $this->editingOpportunityId = $opportunity->id;
        $this->showFormModal = true;
    }

    public function openDetailModal(int $opportunityId): void
    {
        $opportunity = Opportunity::query()->with('client')->findOrFail($opportunityId);

        $this->authorize('view', $opportunity);

        $this->viewingOpportunityId = $opportunity->id;
        $this->showDetailModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingOpportunityId = null;
    }

    public function saveOpportunity(): void
    {
        $rules = $this->opportunityValidationRules();
        $rules['opportunityTitle'] = $rules['title'];
        unset($rules['title']);

        $validated = $this->validate($rules);

        $estimatedValue = null;

        if (isset($validated['estimated_value']) && $validated['estimated_value'] !== '') {
            $estimatedValue = $validated['estimated_value'];
        }

        $payload = [
            'title' => $validated['opportunityTitle'],
            'client_id' => (int) $validated['client_id'],
            'estimated_value' => $estimatedValue,
            'stage' => $validated['stage'] ?? OpportunityStage::Lead->value,
        ];

        $service = app(OpportunityService::class);

        if ($this->editingOpportunityId === null) {
            $this->authorize('create', Opportunity::class);
            $service->create($payload);
            Flux::toast(variant: 'success', text: __('Opportunity created.'));
        } else {
            $opportunity = Opportunity::query()->findOrFail($this->editingOpportunityId);
            $this->authorize('update', $opportunity);
            $service->update($opportunity, $payload);
            Flux::toast(variant: 'success', text: __('Opportunity updated.'));
        }

        $this->closeFormModal();
        $this->resetForm();
        unset($this->kanbanOpportunities, $this->opportunitiesByStage);
    }

    public function moveStage(int $opportunityId, string $stageValue): void
    {
        $opportunity = Opportunity::query()->findOrFail($opportunityId);

        $this->authorize('update', $opportunity);

        $stage = OpportunityStage::from($stageValue);

        app(OpportunityService::class)->moveStage($opportunity, $stage);

        Flux::toast(variant: 'success', text: __('Stage updated.'));
        unset($this->kanbanOpportunities, $this->opportunitiesByStage);
    }

    private function resetForm(): void
    {
        $this->opportunityTitle = '';
        $this->client_id = null;
        $this->estimated_value = '';
        $this->stage = OpportunityStage::Lead->value;
        $this->editingOpportunityId = null;
        $this->resetValidation();
    }

    private function fillFormFromOpportunity(Opportunity $opportunity): void
    {
        $this->opportunityTitle = $opportunity->title;
        $this->client_id = $opportunity->client_id;
        $this->stage = $opportunity->stage->value;

        if ($opportunity->estimated_value !== null) {
            $this->estimated_value = (string) $opportunity->estimated_value;
        } else {
            $this->estimated_value = '';
        }
    }
}; ?>

<div>
    <div class="space-y-6" data-test="opportunities-page">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Opportunities') }}</flux:heading>

            <flux:button variant="primary" wire:click="openCreateModal" data-test="opportunities-create-button">
                {{ __('New opportunity') }}
            </flux:button>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-4" data-test="opportunities-kanban-scroll">
            @foreach (OpportunityStage::ordered() as $stage)
                <div
                    class="flex w-72 shrink-0 flex-col rounded-lg border border-border-subtle bg-surface"
                    data-test="kanban-column-{{ $stage->value }}"
                >
                    <div class="border-b border-border-subtle px-4 py-3 {{ $stage->headerClasses() }}">
                        <flux:heading size="sm" class="font-semibold">{{ $stage->label() }}</flux:heading>
                        <flux:text class="text-xs font-light">
                            {{ $this->opportunitiesByStage[$stage->value]->count() }}
                        </flux:text>
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-3">
                        @foreach ($this->opportunitiesByStage[$stage->value] as $opportunity)
                            <div
                                class="cursor-pointer rounded-lg border border-border-default bg-app p-3 shadow-sm transition hover:border-border-strong"
                                wire:click="openDetailModal({{ $opportunity->id }})"
                                data-test="opportunity-card-{{ $opportunity->id }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <flux:heading size="sm" class="font-medium text-text-primary">{{ $opportunity->title }}</flux:heading>

                                    @if (! empty($opportunity->ai_recommendations))
                                        <flux:badge size="sm" color="purple">{{ __('AI') }}</flux:badge>
                                    @endif
                                </div>

                                <flux:text class="mt-1 text-sm font-light text-text-secondary">
                                    {{ $opportunity->client->company_name }}
                                </flux:text>

                                @if ($opportunity->estimated_value !== null)
                                    <flux:text class="mt-2 text-sm text-text-primary">
                                        {{ Number::currency((float) $opportunity->estimated_value, config('app.currency', 'USD')) }}
                                    </flux:text>
                                @endif

                                <flux:text class="mt-2 text-xs font-light text-text-muted">
                                    {{ __('Next follow-up') }}: —
                                </flux:text>

                                <div class="mt-3 flex justify-end" wire:click.stop>
                                    <flux:dropdown>
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="arrows-right-left"
                                            data-test="opportunity-move-stage"
                                        />
                                        <flux:menu>
                                            @foreach (OpportunityStage::ordered() as $targetStage)
                                                @if ($targetStage !== $opportunity->stage)
                                                    <flux:menu.item wire:click="moveStage({{ $opportunity->id }}, '{{ $targetStage->value }}')">
                                                        {{ $targetStage->label() }}
                                                    </flux:menu.item>
                                                @endif
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <flux:modal wire:model.self="showFormModal" class="max-w-lg" data-test="opportunities-form-modal">
        <form wire:submit="saveOpportunity" class="space-y-6">
            <flux:heading size="lg">
                @if ($editingOpportunityId === null)
                    {{ __('New opportunity') }}
                @else
                    {{ __('Edit opportunity') }}
                @endif
            </flux:heading>

            <flux:input wire:model="opportunityTitle" name="title" :label="__('Title')" required data-test="opportunities-form-title" />

            <flux:select wire:model="client_id" name="client_id" :label="__('Client')" required data-test="opportunities-form-client">
                <flux:select.option value="">{{ __('Select a client') }}</flux:select.option>
                @foreach ($this->clientOptions as $client)
                    <flux:select.option value="{{ $client->id }}">{{ $client->company_name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="estimated_value" name="estimated_value" :label="__('Estimated value')" type="number" step="0.01" min="0" />

            <flux:select wire:model="stage" name="stage" :label="__('Stage')" data-test="opportunities-form-stage">
                @foreach (OpportunityStage::ordered() as $stageOption)
                    <flux:select.option value="{{ $stageOption->value }}">{{ $stageOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeFormModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="opportunities-form-submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDetailModal" class="max-w-lg" data-test="opportunities-detail-modal">
        @if ($this->viewingOpportunity)
            @php($opportunity = $this->viewingOpportunity)

            <div class="space-y-6">
                <flux:heading size="lg">{{ $opportunity->title }}</flux:heading>

                <div class="rounded-lg border border-border-subtle p-4" data-test="opportunities-detail-client">
                    <flux:subheading>{{ __('Client') }}</flux:subheading>
                    <flux:text class="mt-1 font-medium">{{ $opportunity->client->company_name }}</flux:text>
                    @if ($opportunity->client->website)
                        <flux:text class="mt-1 text-sm font-light text-text-secondary">{{ $opportunity->client->website }}</flux:text>
                    @endif
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-text-muted">{{ __('Stage') }}</flux:text>
                        <flux:text>{{ $opportunity->stage->label() }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-text-muted">{{ __('Estimated value') }}</flux:text>
                        <flux:text>
                            @if ($opportunity->estimated_value !== null)
                                {{ Number::currency((float) $opportunity->estimated_value, config('app.currency', 'USD')) }}
                            @else
                                —
                            @endif
                        </flux:text>
                    </div>
                </dl>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="closeDetailModal">{{ __('Close') }}</flux:button>
                    <flux:button type="button" variant="primary" wire:click="openEditModal({{ $opportunity->id }})">{{ __('Edit') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
