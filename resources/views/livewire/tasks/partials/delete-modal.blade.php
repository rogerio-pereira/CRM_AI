<flux:modal wire:model.self="showDeleteModal" class="max-w-lg" data-test="tasks-delete-modal">
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Delete task') }}</flux:heading>

        @if ($this->deleteTask)
            <flux:text>
                {{ __('Are you sure you want to delete ":title"? This action cannot be undone.', ['title' => $this->deleteTask->title]) }}
            </flux:text>
        @endif

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" data-test="tasks-delete-cancel">
                    {{ __('Cancel') }}
                </flux:button>
            </flux:modal.close>

            <flux:button
                variant="danger"
                wire:click="confirmDelete"
                data-test="tasks-delete-confirm"
            >
                {{ __('Delete') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
