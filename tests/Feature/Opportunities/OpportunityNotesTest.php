<?php

namespace Tests\Feature\Opportunities;

use App\Enums\PipelineStage;
use App\Livewire\Opportunities\Index;
use App\Livewire\Opportunities\NotesTimeline;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_detail_renders_notes_timeline(): void
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
            ->assertSee('Taylor Closer')
            ->assertSee('Owner asked for a brochure site.');
    }

    public function test_notes_timeline_renders_after_ai_insights(): void
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

    public function test_empty_timeline_shows_empty_state(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create();

        $this->actingAs($user);

        Livewire::test(NotesTimeline::class, [
                                'opportunityId' => $opportunity->id,
                            ])
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

        Livewire::test(NotesTimeline::class, [
                                'opportunityId' => $opportunity->id,
                            ])
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

        Livewire::test(NotesTimeline::class, [
                                'opportunityId' => $opportunity->id,
                            ])
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

        Livewire::test(NotesTimeline::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->set('body', '   ')
            ->call('addNote')
            ->assertHasErrors(['body']);

        $this->assertDatabaseCount('opportunity_notes', 0);
    }

    public function test_guests_cannot_add_notes(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();

        Livewire::test(NotesTimeline::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->set('body', 'Unauthorized note.')
            ->call('addNote')
            ->assertForbidden();

        $this->assertDatabaseCount('opportunity_notes', 0);
    }
}
