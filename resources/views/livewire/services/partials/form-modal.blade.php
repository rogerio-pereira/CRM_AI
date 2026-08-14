<flux:modal wire:model.self="showFormModal" class="max-w-2xl" data-test="services-form-modal">
    <form wire:submit="saveService" class="space-y-4">
        <flux:heading size="lg">
            @if ($editingServiceId === null)
                {{ __('Add service') }}
            @else
                {{ __('Edit service') }}
            @endif
        </flux:heading>

        <flux:input
            wire:model="name"
            :label="__('Name')"
            required
            data-test="services-form-name"
        />

        <flux:select
            wire:model="category_slug"
            :label="__('Qualification category')"
            data-test="services-form-category"
        >
            @foreach (\App\Enums\CommercialServiceCategory::cases() as $category)
                <flux:select.option value="{{ $category->value }}">{{ $category->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model="default_unit_price"
            type="number"
            min="0"
            max="9999999999999.99"
            step="0.01"
            :label="__('Default unit price')"
            required
            data-test="services-form-price"
        />

        <flux:textarea
            wire:model="description"
            :label="__('Description')"
            rows="4"
            data-test="services-form-description"
        />

        <flux:checkbox
            wire:model="is_active"
            :label="__('Active service')"
            data-test="services-form-active"
        />

        <flux:text class="text-xs text-text-muted">
            {{ __('This price is a proposal default only. Proposal overrides do not change the catalog.') }}
        </flux:text>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" data-test="services-form-cancel">
                    {{ __('Cancel') }}
                </flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="primary" data-test="services-form-submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
