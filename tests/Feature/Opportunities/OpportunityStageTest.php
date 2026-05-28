<?php

namespace Tests\Feature\Opportunities;

use App\Enums\OpportunityStage;
use App\Events\OpportunityStageChanged;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\OpportunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_move_stage_updates_opportunity_and_dispatches_event(): void
    {
        Event::fake([OpportunityStageChanged::class]);

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'stage' => OpportunityStage::Lead,
        ]);

        $this->actingAs($user);

        app(OpportunityService::class)->moveStage($opportunity, OpportunityStage::Contact);

        $this->assertSame(OpportunityStage::Contact, $opportunity->fresh()->stage);

        Event::assertDispatched(OpportunityStageChanged::class, function (OpportunityStageChanged $event) use ($opportunity, $user): bool {
            return $event->opportunityId === $opportunity->id
                && $event->from === OpportunityStage::Lead
                && $event->to === OpportunityStage::Contact
                && $event->userId === $user->id;
        });
    }

    public function test_move_stage_via_livewire_places_card_in_target_column(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'stage' => OpportunityStage::Lead,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::opportunities.index')
            ->call('moveStage', $opportunity->id, OpportunityStage::Won->value);

        $this->assertSame(OpportunityStage::Won, $opportunity->fresh()->stage);
    }

    public function test_won_and_lost_are_terminal_stages(): void
    {
        $this->assertTrue(OpportunityStage::Won->isTerminal());
        $this->assertTrue(OpportunityStage::Lost->isTerminal());
        $this->assertFalse(OpportunityStage::Lead->isTerminal());
    }
}
