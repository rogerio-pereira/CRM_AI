<?php

namespace Tests\Feature\Opportunities;

use App\Enums\PipelineStage;
use App\Livewire\Opportunities\Index;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_detail_renders_notes(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Taylor Closer']);
        $opportunity = Opportunity::factory()
                            ->create(['title' => 'Notes Detail Deal']);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner asked for a brochure site.',
            ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="opportunities-detail-notes"')
            ->assertSeeHtml('data-test="opportunities-detail-notes-list"')
            ->assertSeeHtml('data-test="opportunities-detail-note-delete-')
            ->assertSeeHtml('Livewire.find($event.currentTarget.dataset.indexComponentId)')
            ->assertSee('Taylor Closer')
            ->assertSee('Owner asked for a brochure site.');
    }

    public function test_opportunity_detail_renders_ai_authored_notes(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Taylor Closer']);
        $opportunity = Opportunity::factory()
                            ->create(['title' => 'AI Note Deal']);
        $note = OpportunityNote::factory()
                    ->for($opportunity)
                    ->create([
                        'user_id' => null,
                        'body' => 'No public email.',
                    ]);

        $this->actingAs($user);

        $html = Livewire::test(Index::class)
                    ->call('openDetailModal', $opportunity->id)
                    ->html();
        $authorMarker = 'data-test="opportunities-detail-note-author-'.$note->id.'"';
        $authorPosition = strpos($html, $authorMarker);

        $this->assertNotFalse($authorPosition);

        $authorSlice = substr($html, $authorPosition, 200);

        $this->assertStringContainsString('AI', $authorSlice);
        $this->assertStringContainsString('No public email.', $html);
    }

    public function test_notes_render_after_ai_insights(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Taylor Closer']);
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create(['title' => 'Notes After Insights Deal']);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner asked for a brochure site.',
            ]);

        $this->actingAs($user);

        $html = Livewire::test(Index::class)
                    ->call('openDetailModal', $opportunity->id)
                    ->html();
        $insightsPosition = strpos($html, 'data-test="opportunities-detail-ai-insights"');
        $notesPosition = strpos($html, 'data-test="opportunities-detail-notes"');

        $this->assertNotFalse($insightsPosition);
        $this->assertNotFalse($notesPosition);
        $this->assertGreaterThan($insightsPosition, $notesPosition);
    }

    public function test_empty_notes_show_empty_state(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="opportunities-detail-notes-empty"')
            ->assertSee('No notes yet.');
    }

    public function test_authenticated_user_can_add_a_note(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Jordan Sales']);
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Contact,
                            ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->set('body', 'Called the owner this morning.')
            ->call('addNote')
            ->assertHasNoErrors()
            ->assertSee('Called the owner this morning.')
            ->assertSee('Jordan Sales');

        $this->assertDatabaseHas('opportunity_notes', [
            'opportunity_id' => $opportunity->id,
            'user_id' => $user->id,
            'body' => 'Called the owner this morning.',
        ]);

        $opportunity->refresh();

        $this->assertSame(PipelineStage::Contact, $opportunity->stage);
    }

    public function test_notes_are_listed_newest_first(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Older note.',
                'created_at' => Carbon::now()
                                    ->subHour(),
            ]);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Newer note.',
                'created_at' => Carbon::now(),
            ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeInOrder([
                'Newer note.',
                'Older note.',
            ]);
    }

    public function test_body_is_required_when_adding_a_note(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->set('body', '   ')
            ->call('addNote')
            ->assertHasErrors(['body']);

        $this->assertDatabaseCount('opportunity_notes', 0);
    }

    public function test_authenticated_user_can_delete_a_note(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();
        $note = OpportunityNote::factory()
                    ->for($opportunity)
                    ->for($user)
                    ->create([
                        'body' => 'Note to remove.',
                    ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $opportunity->id)
            ->call('deleteNote', $note->id)
            ->assertDontSee('Note to remove.');

        $this->assertDatabaseMissing('opportunity_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_delete_note_does_not_remove_notes_from_another_opportunity(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();
        $otherOpportunity = Opportunity::factory()
                            ->create();
        $otherNote = OpportunityNote::factory()
                            ->for($otherOpportunity)
                            ->for($user)
                            ->create([
                                'body' => 'Belongs to another opportunity.',
                            ]);

        $this->actingAs($user);

        try {
            Livewire::test(Index::class)
                ->call('openDetailModal', $opportunity->id)
                ->call('deleteNote', $otherNote->id);
            $this->fail('Expected the note from another opportunity to be missing.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('opportunity_notes', [
                'id' => $otherNote->id,
            ]);
        }
    }

    public function test_add_note_does_nothing_when_detail_modal_is_closed(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('body', 'Should not be saved.')
            ->call('addNote')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('opportunity_notes', 0);
    }

    public function test_delete_note_does_nothing_when_detail_modal_is_closed(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();
        $note = OpportunityNote::factory()
                    ->for($opportunity)
                    ->for($user)
                    ->create([
                        'body' => 'Should remain.',
                    ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('deleteNote', $note->id);

        $this->assertDatabaseHas('opportunity_notes', [
            'id' => $note->id,
        ]);
    }
}
