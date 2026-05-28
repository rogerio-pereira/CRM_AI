<flux:modal wire:model.self="showDeleteModal" class="max-w-lg" data-test="leads-delete-modal">
    @if ($this->deleteClient)
        <form wire:submit="confirmDelete" class="space-y-6">
            <flux:heading size="lg">{{ __('Delete lead') }}</flux:heading>

            <flux:text class="text-text-secondary">
                {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $this->deleteClient->company_name]) }}
            </flux:text>

            @error('delete')
                <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" data-test="leads-delete-confirm">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </form>
    @endif
</flux:modal>
