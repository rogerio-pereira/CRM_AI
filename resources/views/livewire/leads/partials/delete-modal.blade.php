<flux:modal wire:model.self="showDeleteModal" class="max-w-lg" data-test="leads-delete-modal">
    <form wire:submit="deleteClient" class="space-y-6">
        <flux:heading size="lg">{{ __('Delete client?') }}</flux:heading>
        <flux:text class="font-light text-text-secondary">
            {{ __('This action soft-deletes the client record.') }}
        </flux:text>

        @error('delete')
            <flux:text class="text-danger">{{ $message }}</flux:text>
        @enderror

        <div class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" wire:click="closeDeleteModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="danger" data-test="leads-delete-confirm">{{ __('Delete') }}</flux:button>
        </div>
    </form>
</flux:modal>
