<?php

use App\Enums\PipelineStage;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;

it('redirects guests away from the opportunity notes UI', function () {
    visit('/opportunities')
        ->assertPathIs('/login')
        ->assertNotPresent('[data-test="opportunities-detail-notes"]');
});

it('shows an empty notes state on opportunity detail', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->create([
                            'title' => 'Empty Notes Browser Deal',
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-notes"]')
        ->assertPresent('[data-test="opportunities-detail-notes-empty"]')
        ->assertSee('No notes yet.')
        ->assertNotPresent('[data-test="opportunities-detail-notes-list"]');
});

it('lists notes newest first with author and timestamp', function () {
    $user = User::factory()
                ->create(['name' => 'Taylor Closer']);
    $opportunity = Opportunity::factory()
                        ->create([
                            'title' => 'Timeline Notes Deal',
                        ]);
    $olderCreatedAt = Carbon::now()
                            ->subHour();
    $newerCreatedAt = Carbon::now();
    $olderNote = OpportunityNote::factory()
                    ->for($opportunity)
                    ->for($user)
                    ->create([
                        'body' => 'Older note on the timeline.',
                        'created_at' => $olderCreatedAt,
                    ]);
    $newerNote = OpportunityNote::factory()
                    ->for($opportunity)
                    ->for($user)
                    ->create([
                        'body' => 'Newer note on the timeline.',
                        'created_at' => $newerCreatedAt,
                    ]);
    $olderNoteCreatedAt = $olderNote->created_at;
    $newerNoteCreatedAt = $newerNote->created_at;
    $olderTimestamp = $olderNoteCreatedAt->format('M j, Y g:i A');
    $newerTimestamp = $newerNoteCreatedAt->format('M j, Y g:i A');
    $firstNoteSelector = '[data-test="opportunities-detail-notes-list"] li:first-child';
    $secondNoteSelector = '[data-test="opportunities-detail-notes-list"] li:nth-child(2)';

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-notes-list"]')
        ->assertCount('[data-test="opportunities-detail-notes-list"] li', 2)
        ->assertAttribute($firstNoteSelector, 'data-test', 'opportunities-detail-note-'.$newerNote->id)
        ->assertAttribute($secondNoteSelector, 'data-test', 'opportunities-detail-note-'.$olderNote->id)
        ->assertSeeIn('@opportunities-detail-note-author-'.$newerNote->id, 'Taylor Closer')
        ->assertSeeIn('@opportunities-detail-note-author-'.$olderNote->id, 'Taylor Closer')
        ->assertSeeIn('@opportunities-detail-note-created-at-'.$newerNote->id, $newerTimestamp)
        ->assertSeeIn('@opportunities-detail-note-created-at-'.$olderNote->id, $olderTimestamp)
        ->assertSeeIn('@opportunities-detail-note-body-'.$newerNote->id, 'Newer note on the timeline.')
        ->assertSeeIn('@opportunities-detail-note-body-'.$olderNote->id, 'Older note on the timeline.');
});

it('renders notes below AI insights on opportunity detail', function () {
    $user = User::factory()
                ->create(['name' => 'Taylor Closer']);
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Notes After Insights Deal',
                        ]);
    OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner asked for a brochure site.',
            ]);
    $notesFollowInsights = '(() => {
        const insights = document.querySelector(\'[data-test="opportunities-detail-ai-insights"]\');
        const notes = document.querySelector(\'[data-test="opportunities-detail-notes"]\');

        if (insights === null || notes === null) {
            return false;
        }

        return (insights.compareDocumentPosition(notes) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
    })()';

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-insights"]')
        ->assertPresent('[data-test="opportunities-detail-notes"]')
        ->assertSee('Owner asked for a brochure site.')
        ->assertScript($notesFollowInsights, true);
});

it('rejects an empty note body', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->create([
                            'title' => 'Empty Body Notes Deal',
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->click('@opportunities-detail-notes-submit')
        ->waitForText('The body field is required.')
        ->assertPresent('[data-test="opportunities-detail-notes-empty"]');

    $noteCount = OpportunityNote::where('opportunity_id', $opportunity->id)
                    ->count();

    expect($noteCount)
        ->toBe(0);
});

it('rejects a whitespace-only note body', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->create([
                            'title' => 'Whitespace Notes Deal',
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->fill('@opportunities-detail-notes-body', '   ')
        ->click('@opportunities-detail-notes-submit')
        ->waitForText('The body field is required.')
        ->assertPresent('[data-test="opportunities-detail-notes-empty"]');

    $noteCount = OpportunityNote::where('opportunity_id', $opportunity->id)
                    ->count();

    expect($noteCount)
        ->toBe(0);
});

it('keeps line breaks when adding a multiline note', function () {
    $user = User::factory()
                ->create(['name' => 'Jordan Sales']);
    $opportunity = Opportunity::factory()
                        ->create([
                            'title' => 'Multiline Notes Deal',
                            'stage' => PipelineStage::Contact,
                        ]);
    $noteBody = "Called the owner.\nThey want a brochure site.";

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->fill('@opportunities-detail-notes-body', $noteBody)
        ->click('@opportunities-detail-notes-submit')
        ->waitForText('Note added.')
        ->assertSee('Called the owner.')
        ->assertSee('They want a brochure site.')
        ->assertSee('Jordan Sales')
        ->assertValue('@opportunities-detail-notes-body', '');

    $note = OpportunityNote::where('opportunity_id', $opportunity->id)
                ->first();

    expect($note)
        ->not
        ->toBeNull();
    expect($note->body)
        ->toBe($noteBody);

    $opportunity->refresh();

    expect($opportunity->stage)
        ->toBe(PipelineStage::Contact);
});

it('restores the empty state after deleting the last note', function () {
    $user = User::factory()
                ->create(['name' => 'Browser Note Author']);
    $opportunity = Opportunity::factory()
                        ->create([
                            'title' => 'Last Note Browser Deal',
                        ]);
    $note = OpportunityNote::factory()
                ->for($opportunity)
                ->for($user)
                ->create([
                    'body' => 'Only note on this opportunity.',
                ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-notes-list"]')
        ->click('@opportunities-detail-note-delete-'.$note->id)
        ->assertNoJavaScriptErrors()
        ->waitForText('Note deleted.')
        ->assertDontSee('Only note on this opportunity.')
        ->assertPresent('[data-test="opportunities-detail-notes-empty"]')
        ->assertNotPresent('[data-test="opportunities-detail-notes-list"]');

    $noteExists = OpportunityNote::where('id', $note->id)
                        ->exists();

    expect($noteExists)
        ->toBeFalse();
});
