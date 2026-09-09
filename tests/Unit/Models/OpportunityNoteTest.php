<?php

namespace Tests\Unit\Models;

use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_relationship_returns_related_opportunity(): void
    {
        $opportunity = Opportunity::factory()
                            ->create();
        $note = OpportunityNote::factory()
                    ->for($opportunity)
                    ->create();
        $relatedOpportunity = $note->opportunity;

        $this->assertTrue($relatedOpportunity->is($opportunity));
    }

    public function test_user_relationship_returns_related_author(): void
    {
        $user = User::factory()
                    ->create(['name' => 'Note Author']);
        $note = OpportunityNote::factory()
                    ->for($user)
                    ->create();
        $relatedUser = $note->user;

        $this->assertTrue($relatedUser->is($user));
        $this->assertSame('Note Author', $relatedUser->name);
        $this->assertSame('Note Author', $note->authorName());
    }

    public function test_author_name_is_ai_when_the_note_has_no_user(): void
    {
        $note = OpportunityNote::factory()
                    ->create([
                        'user_id' => null,
                        'body' => 'No public email.',
                    ]);

        $this->assertNull($note->user);
        $this->assertSame('AI', $note->authorName());
    }
}
