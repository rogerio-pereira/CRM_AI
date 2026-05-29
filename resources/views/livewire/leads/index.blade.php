<div class="space-y-6" data-test="leads-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Leads / Clients') }}</flux:heading>

        <flux:button
            variant="primary"
            wire:click="openCreateModal"
            data-test="leads-create-button"
        >
            {{ __('New lead') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            :placeholder="__('Search by company name')"
            data-test="leads-search"
        />

        <flux:select
            wire:model.live="statusFilter"
            :label="__('Status')"
            data-test="leads-status-filter"
        >
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            @foreach (\App\Enums\ClientStatus::cases() as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-border-default bg-surface">
        <table class="w-full text-left text-sm font-light text-text-secondary">
            <thead>
                <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                    <th class="h-10 px-4">{{ __('Company') }}</th>
                    <th class="h-10 px-4">{{ __('Contact') }}</th>
                    <th class="h-10 px-4">{{ __('Source') }}</th>
                    <th class="h-10 px-4">{{ __('Status') }}</th>
                    <th class="h-10 px-4 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->clients as $client)
                    <tr
                        wire:key="client-{{ $client->id }}"
                        class="h-12 border-b border-border-subtle odd:bg-transparent even:bg-app/40 hover:bg-hover"
                        data-test="leads-row-{{ $client->id }}"
                    >
                        <td class="px-4 font-medium text-text-primary">{{ $client->company_name }}</td>
                        <td class="px-4">
                            @if ($client->contact_name)
                                <div>{{ $client->contact_name }}</div>
                            @endif
                            @if ($client->contact_email)
                                <div class="text-xs text-text-muted">{{ $client->contact_email }}</div>
                            @endif
                        </td>
                        <td class="px-4">{{ $client->lead_source ?? '—' }}</td>
                        <td class="px-4">
                            <span
                                class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $client->status->badgeClasses() }}"
                                data-test="leads-status-badge-{{ $client->id }}"
                                data-status="{{ $client->status->value }}"
                            >
                                {{ $client->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="ellipsis-horizontal"
                                    data-test="leads-actions-{{ $client->id }}"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="openDetailModal({{ $client->id }})"
                                        data-test="leads-view-{{ $client->id }}"
                                    >
                                        {{ __('View details') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="openEditModal({{ $client->id }})"
                                        data-test="leads-edit-{{ $client->id }}"
                                    >
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    @if ($client->status !== \App\Enums\ClientStatus::Active)
                                        <flux:menu.item
                                            wire:click="setActive({{ $client->id }})"
                                            data-test="leads-active-{{ $client->id }}"
                                        >
                                            {{ __('Mark as active') }}
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.item
                                        wire:click="setContactIntent({{ $client->id }})"
                                        data-test="leads-contact-intent-{{ $client->id }}"
                                    >
                                        {{ __('Mark contact intent') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="setIgnored({{ $client->id }})"
                                        data-test="leads-ignore-{{ $client->id }}"
                                    >
                                        {{ __('Ignore') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="setArchived({{ $client->id }})"
                                        data-test="leads-archive-{{ $client->id }}"
                                    >
                                        {{ __('Archive') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="openDeleteModal({{ $client->id }})"
                                        variant="danger"
                                        data-test="leads-delete-{{ $client->id }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-text-muted" data-test="leads-empty">
                            {{ __('No leads found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('livewire.leads.partials.form-modal')
    @include('livewire.leads.partials.detail-modal')
    @include('livewire.leads.partials.delete-modal')
</div>
