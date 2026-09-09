<?php

namespace App\Livewire\Opportunities;

use App\Concerns\OpportunityNoteValidationRules;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotesTimeline extends Component
{
    use OpportunityNoteValidationRules;

    public int $opportunityId;

    public string $parentComponentId = '';

    public string $body = '';

    /**
     * @return Collection<int, OpportunityNote>
     */
    #[Computed]
    public function timelineNotes(): Collection
    {
        return OpportunityNote::with('user')
                            ->where('opportunity_id', $this->opportunityId)
                            ->orderByDesc('created_at')
                            ->get();
    }

    public function addNote(): void
    {
        $this->body = trim($this->body);
        $validated = $this->validate(self::formRules());

        $opportunity = Opportunity::findOrFail($this->opportunityId);
        $attributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ];

        OpportunityNote::create($attributes);

        $this->body = '';
        unset($this->timelineNotes);

        Flux::toast(
            variant: 'success',
            text: __('Note added.'),
        );
    }

    public function deleteNote(int $noteId): void
    {
        $note = OpportunityNote::where('opportunity_id', $this->opportunityId)
                            ->findOrFail($noteId);
        $note->delete();
        unset($this->timelineNotes);

        Flux::toast(
            variant: 'success',
            text: __('Note deleted.'),
        );
    }

    #[On('opportunity-note-deleted')]
    public function refreshNotes(): void
    {
        unset($this->timelineNotes);
    }

    public function render(): View
    {
        return view('livewire.opportunities.notes-timeline', [
            'parentComponentId' => $this->parentComponentId,
        ]);
    }
}
