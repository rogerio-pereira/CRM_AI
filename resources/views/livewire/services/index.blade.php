<div class="space-y-6" data-test="services-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Services') }}</flux:heading>
            <flux:text class="mt-1 text-text-muted">
                {{ __('Sellable proposal items maintained separately from qualification briefs.') }}
            </flux:text>
        </div>

        <flux:button
            variant="primary"
            wire:click="openCreateModal"
            data-test="services-create-button"
        >
            {{ __('Add service') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            :placeholder="__('Search by name or description')"
            data-test="services-search"
        />

        <flux:select
            wire:model.live="categoryFilter"
            :label="__('Category')"
            data-test="services-category-filter"
        >
            <flux:select.option value="all">{{ __('All categories') }}</flux:select.option>
            @foreach (\App\Enums\CommercialServiceCategory::cases() as $category)
                <flux:select.option value="{{ $category->value }}">{{ $category->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model.live="activeFilter"
            :label="__('Status')"
            data-test="services-active-filter"
        >
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-border-default bg-surface">
        <table class="w-full min-w-[48rem] text-left text-sm font-light text-text-secondary">
            <thead>
                <tr class="border-b border-border-default text-xs font-bold uppercase text-text-muted">
                    <th class="h-10 px-4">{{ __('Name') }}</th>
                    <th class="h-10 px-4">{{ __('Category') }}</th>
                    <th class="h-10 px-4">{{ __('Default price') }}</th>
                    <th class="h-10 px-4">{{ __('Active') }}</th>
                    <th class="h-10 px-4 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->services as $service)
                    <tr
                        wire:key="commercial-service-{{ $service->id }}"
                        class="h-12 border-b border-border-subtle hover:bg-hover"
                        data-test="services-row-{{ $service->id }}"
                    >
                        <td class="px-4">
                            <div class="font-medium text-text-primary">{{ $service->name }}</div>
                            @if ($service->description !== null)
                                <div class="mt-1 max-w-md truncate text-xs text-text-muted">
                                    {{ $service->description }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4">{{ $service->category_slug->label() }}</td>
                        <td class="px-4">{{ number_format((float) $service->default_unit_price, 2) }}</td>
                        <td class="px-4">
                            <span
                                @class([
                                    'inline-flex rounded-full border px-2 py-0.5 text-xs font-medium',
                                    'border-status-success/50 bg-status-success/20 text-status-success' => $service->is_active,
                                    'border-status-neutral/50 bg-status-neutral/20 text-status-neutral' => ! $service->is_active,
                                ])
                                data-test="services-status-{{ $service->id }}"
                                @if ($service->is_active)
                                    data-active="true"
                                @else
                                    data-active="false"
                                @endif
                            >
                                @if ($service->is_active)
                                    {{ __('Active') }}
                                @else
                                    {{ __('Inactive') }}
                                @endif
                            </span>
                        </td>
                        <td class="px-4 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="ellipsis-horizontal"
                                    data-test="services-actions-{{ $service->id }}"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="openEditModal({{ $service->id }})"
                                        data-test="services-edit-{{ $service->id }}"
                                    >
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="toggleActive({{ $service->id }})"
                                        data-test="services-toggle-active-{{ $service->id }}"
                                    >
                                        @if ($service->is_active)
                                            {{ __('Deactivate') }}
                                        @else
                                            {{ __('Activate') }}
                                        @endif
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-text-muted" data-test="services-empty">
                            {{ __('No services found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->services->hasPages())
            <div class="border-t border-border-default px-4 py-3" data-test="services-pagination">
                <flux:pagination :paginator="$this->services" />
            </div>
        @endif
    </div>

    @include('livewire.services.partials.form-modal')
</div>
