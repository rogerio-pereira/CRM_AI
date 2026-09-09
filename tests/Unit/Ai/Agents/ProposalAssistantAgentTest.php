<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\ProposalAssistantAgent;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalAssistantAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_includes_opportunity_notes_as_context(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $user = User::factory()
                    ->create(['name' => 'Pat Closer']);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Budget is around 8k.',
            ]);

        $agent = app(ProposalAssistantAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => $opportunity->id,
        ]);
        $notes = $result['opportunity_notes'];
        $firstNote = $notes[0];

        $this->assertSame('stub', $result['status']);
        $this->assertSame('Budget is around 8k.', $firstNote['body']);
        $this->assertSame('Pat Closer', $firstNote['author']);
    }

    public function test_handle_returns_empty_notes_when_opportunity_is_missing(): void
    {
        $agent = app(ProposalAssistantAgent::class);
        $result = $agent->handle([
                            'opportunity_id' => 99999,
        ]);

        $this->assertSame([], $result['opportunity_notes']);
    }

    public function test_handle_returns_empty_notes_when_opportunity_id_is_absent(): void
    {
        $agent = app(ProposalAssistantAgent::class);
        $result = $agent->handle([]);

        $this->assertSame([], $result['opportunity_notes']);
    }
}
