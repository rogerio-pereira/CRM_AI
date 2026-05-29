<flux:modal wire:model.self="showFormModal" class="max-w-lg" data-test="tasks-quick-create-modal">
    <form wire:submit="saveTask" class="space-y-4">
        <flux:heading size="lg">{{ __('New task') }}</flux:heading>

        <flux:input
            wire:model="title"
            :label="__('Title')"
            required
            data-test="tasks-quick-form-title"
        />

        <flux:select
            wire:model.live="client_id"
            :label="__('Client')"
            placeholder="{{ __('Select a client') }}"
            data-test="tasks-quick-form-client"
        >
            @foreach ($this->clientOptions as $client)
                <flux:select.option value="{{ $client->id }}">{{ $client->company_name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model="opportunity_id"
            :label="__('Opportunity')"
            placeholder="{{ __('Optional opportunity') }}"
            data-test="tasks-quick-form-opportunity"
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
            data-test="tasks-quick-form-due-at"
        />

        <flux:select
            wire:model="priority"
            :label="__('Priority')"
            data-test="tasks-quick-form-priority"
        >
            @foreach (\App\Enums\TaskPriority::cases() as $priority)
                <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea
            wire:model="description"
            :label="__('Description')"
            rows="3"
            data-test="tasks-quick-form-description"
        />

        <flux:checkbox
            wire:model="is_important"
            :label="__('Important task')"
            data-test="tasks-quick-form-important"
        />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" data-test="tasks-quick-form-cancel">
                    {{ __('Cancel') }}
                </flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="primary" data-test="tasks-quick-form-submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
