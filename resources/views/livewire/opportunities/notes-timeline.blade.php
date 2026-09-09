<div class="space-y-4" data-test="opportunities-detail-notes">
    <flux:subheading>{{ __('Notes') }}</flux:subheading>

    @if ($this->timelineNotes->isEmpty())
        <flux:text class="text-text-muted" data-test="opportunities-detail-notes-empty">
            {{ __('No notes yet.') }}
        </flux:text>
    @else
        <ul class="space-y-3" data-test="opportunities-detail-notes-list">
            @foreach ($this->timelineNotes as $note)
                <li
                    class="rounded-lg border border-border bg-elevated p-3"
                    data-test="opportunities-detail-note-{{ $note->id }}"
                >
                    <div class="flex items-center justify-between gap-2 text-xs text-text-muted">
                        <span data-test="opportunities-detail-note-author-{{ $note->id }}">
                            {{ $note->user->name }}
                        </span>
                        <span data-test="opportunities-detail-note-created-at-{{ $note->id }}">
                            {{ $note->created_at->format('M j, Y g:i A') }}
                        </span>
                    </div>
                    <flux:text class="mt-2 whitespace-pre-wrap text-text-secondary" data-test="opportunities-detail-note-body-{{ $note->id }}">
                        {{ $note->body }}
                    </flux:text>
                </li>
            @endforeach
        </ul>
    @endif

    <form wire:submit="addNote" class="space-y-3">
        <flux:textarea
            wire:model="body"
            name="body"
            :label="__('Add a note')"
            rows="3"
            data-test="opportunities-detail-notes-body"
        />

        <div class="flex justify-end">
            <flux:button
                type="submit"
                variant="primary"
                size="sm"
                data-test="opportunities-detail-notes-submit"
            >
                {{ __('Add note') }}
            </flux:button>
        </div>
    </form>
</div>
