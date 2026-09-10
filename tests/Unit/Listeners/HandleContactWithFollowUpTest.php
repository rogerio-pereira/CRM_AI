<?php

namespace Tests\Unit\Listeners;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpReminderStatus;
use App\Enums\PipelineStage;
use App\Events\ContactWithFollowUp;
use App\Listeners\HandleContactWithFollowUp;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleContactWithFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_handle_moves_opportunity_to_contact_sent_and_creates_follow_up(): void
    {
        Carbon::setTestNow('2026-09-09 13:05:00');

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::Contact,
                            ]);
        $event = new ContactWithFollowUp($opportunity, $user->id);
        $listener = app(HandleContactWithFollowUp::class);

        $listener->handle($event);

        $expectedDueAt = Carbon::parse('2026-09-12 09:00:00');
        $followUp = FollowUp::where('opportunity_id', $opportunity->id)
                        ->first();

        $this->assertDatabaseHas('opportunities', [
                                'id' => $opportunity->id,
                                'stage' => PipelineStage::ContactSent->value,
        ]);
        $this->assertNotNull($followUp);
        $this->assertSame($opportunity->client_id, $followUp->client_id);
        $this->assertSame(FollowUpPriority::Medium, $followUp->priority);
        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame('Follow up after first-contact email.', $followUp->notes);
        $this->assertTrue($expectedDueAt->equalTo($followUp->due_at));

        $note = OpportunityNote::where('opportunity_id', $opportunity->id)
                    ->first();

        $this->assertNotNull($note);
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame('First Email sent', $note->body);
    }

    public function test_handle_follow_up_creates_the_last_reminder(): void
    {
        Carbon::setTestNow('2026-09-09 13:05:00');

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $event = new ContactWithFollowUp($opportunity, $user->id);
        $listener = app(HandleContactWithFollowUp::class);

        $listener->handle($event);

        $expectedDueAt = Carbon::parse('2026-09-12 09:00:00');
        $followUp = FollowUp::where('opportunity_id', $opportunity->id)
                        ->first();

        $this->assertDatabaseHas('opportunities', [
                                'id' => $opportunity->id,
                                'stage' => PipelineStage::ContactSent->value,
        ]);
        $this->assertNotNull($followUp);
        $this->assertSame(FollowUpPriority::Medium, $followUp->priority);
        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame('Send the last follow-up email.', $followUp->notes);
        $this->assertTrue($expectedDueAt->equalTo($followUp->due_at));

        $note = OpportunityNote::where('opportunity_id', $opportunity->id)
                    ->first();

        $this->assertNotNull($note);
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame('Follow-up 1 sent', $note->body);
    }
}
