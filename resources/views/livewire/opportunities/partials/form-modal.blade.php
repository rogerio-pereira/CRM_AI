<flux:modal wire:model.self="showFormModal" class="max-w-lg" data-test="opportunities-form-modal">
    <form wire:submit="saveOpportunity" class="space-y-4">
        <flux:heading size="lg">
            @if ($editingOpportunityId === null)
                {{ __('Add Opportunity') }}
            @else
                {{ __('Edit Opportunity') }}
            @endif
        </flux:heading>

        <flux:input
            wire:model="title"
            :label="__('Title')"
            required
            data-test="opportunities-form-title"
        />

        <flux:select
            wire:model="client_id"
            :label="__('Client')"
            placeholder="{{ __('Select a client') }}"
            data-test="opportunities-form-client"
        >
            @foreach ($this->clientOptions as $client)
                <flux:select.option value="{{ $client->id }}">{{ $client->company_name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model="estimated_value"
            type="number"
            step="0.01"
            min="0"
            :label="__('Estimated value')"
            data-test="opportunities-form-value"
        />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" data-test="opportunities-form-cancel">
                    {{ __('Cancel') }}
                </flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="primary" data-test="opportunities-form-submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
