<div>
    <div class="space-y-6" data-test="leads-page">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Leads / Clients') }}</flux:heading>

            <flux:button variant="primary" wire:click="openCreateModal" data-test="leads-create-button">
                {{ __('New client') }}
            </flux:button>
        </div>

        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    :label="__('Search')"
                    :placeholder="__('Search by company name')"
                    data-test="leads-search"
                />
            </div>

            <div class="w-full md:w-56">
                <flux:select wire:model.live="statusFilter" :label="__('Status')" data-test="leads-status-filter">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    @foreach (\App\Enums\ClientStatus::filterable() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table :paginate="$this->clients" class="rounded-lg border border-border-subtle bg-surface">
            <flux:table.columns>
                <flux:table.column>{{ __('Company') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Lead source') }}</flux:table.column>
                <flux:table.column>{{ __('Website') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->clients as $client)
                    <flux:table.row wire:key="client-{{ $client->id }}" class="odd:bg-app/40">
                        <flux:table.cell>
                            <button
                                type="button"
                                class="font-medium text-brand-primary hover:underline"
                                wire:click="openDetailModal({{ $client->id }})"
                                data-test="leads-row-{{ $client->id }}"
                            >
                                {{ $client->company_name }}
                            </button>
                        </flux:table.cell>
                        <flux:table.cell>{{ $client->status->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $client->lead_source ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($client->website)
                                <a href="{{ $client->website }}" target="_blank" rel="noopener noreferrer" class="text-brand-primary hover:underline">
                                    {{ parse_url($client->website, PHP_URL_HOST) ?? $client->website }}
                                </a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-end">
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" data-test="leads-actions-{{ $client->id }}" />
                                <flux:menu>
                                    <flux:menu.item wire:click="openEditModal({{ $client->id }})">{{ __('Edit') }}</flux:menu.item>
                                    <flux:menu.item wire:click="archiveClient({{ $client->id }})">{{ __('Archive') }}</flux:menu.item>
                                    <flux:menu.item wire:click="ignoreClient({{ $client->id }})">{{ __('Ignore') }}</flux:menu.item>
                                    <flux:menu.item wire:click="markContactIntent({{ $client->id }})">{{ __('Contact intent') }}</flux:menu.item>
                                    <flux:menu.item wire:click="openDeleteModal({{ $client->id }})" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-text-secondary">
                            {{ __('No clients found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @include('livewire.leads.partials.form-modal')
    @include('livewire.leads.partials.detail-modal')
    @include('livewire.leads.partials.delete-modal')
</div>
