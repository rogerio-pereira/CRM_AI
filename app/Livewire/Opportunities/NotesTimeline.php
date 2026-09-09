<?php

namespace App\Livewire\Opportunities;

use App\Models\Opportunity;
use App\Models\OpportunityNote;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotesTimeline extends Component
{
    public int $opportunityId;

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
        $userId = auth()->id();

        if ($userId === null) {
            abort(403);
        }

        $trimmedBody = trim($this->body);
        $this->body = $trimmedBody;
        $rules = [
            'body' => ['required', 'string', 'max:5000'],
        ];
        $validated = $this->validate($rules);

        $opportunity = Opportunity::findOrFail($this->opportunityId);
        $attributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => $userId,
            'body' => $validated['body'],
        ];

        OpportunityNote::create($attributes);

        $this->body = '';
        $this->resetValidation();
        unset($this->timelineNotes);

        Flux::toast(
            variant: 'success',
            text: __('Note added.'),
        );
    }

    public function render(): View
    {
        return view('livewire.opportunities.notes-timeline');
    }
}
