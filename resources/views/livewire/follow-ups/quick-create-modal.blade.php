<flux:modal wire:model.self="showFormModal" class="max-w-lg" data-test="follow-ups-quick-create-modal">
    <form wire:submit="saveFollowUp" class="space-y-4">
        <flux:heading size="lg">{{ __('New follow-up') }}</flux:heading>

        <flux:select
            wire:model.live="client_id"
            :label="__('Client')"
            placeholder="{{ __('Select a client') }}"
            data-test="follow-ups-form-client"
        >
            @foreach ($this->clientOptions as $client)
                <flux:select.option value="{{ $client->id }}">{{ $client->company_name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model="opportunity_id"
            :label="__('Opportunity')"
            placeholder="{{ __('Optional opportunity') }}"
            data-test="follow-ups-form-opportunity"
        >
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->opportunityOptions as $opportunity)
                <flux:select.option value="{{ $opportunity->id }}">{{ $opportunity->title }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model="due_at"
            type="datetime-local"
            :label="__('Due date')"
            required
            data-test="follow-ups-form-due-at"
        />

        <flux:select
            wire:model="priority"
            :label="__('Priority')"
            data-test="follow-ups-form-priority"
        >
            @foreach (\App\Enums\FollowUpPriority::cases() as $priority)
                <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea
            wire:model="notes"
            :label="__('Notes')"
            rows="3"
            data-test="follow-ups-form-notes"
        />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" data-test="follow-ups-form-cancel">
                    {{ __('Cancel') }}
                </flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="primary" data-test="follow-ups-form-submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
