<?php

namespace Tests\Unit\Models;

use App\Enums\FollowUpReminderStatus;
use App\Enums\FollowUpSequenceStep;
use App\Enums\PipelineStage;
use App\Models\FollowUp;
use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_badge_classes_use_danger_when_overdue(): void
    {
        $followUp = FollowUp::factory()
                        ->overdue()
                        ->create();

        $this->assertStringContainsString('status-danger', $followUp->statusBadgeClasses());
    }

    public function test_status_badge_classes_use_reminder_status_when_not_overdue(): void
    {
        $dueAt = Carbon::now()
                        ->addDay();

        $followUp = FollowUp::factory()
                        ->create([
                            'due_at' => $dueAt,
                            'reminder_status' => FollowUpReminderStatus::Pending,
                        ]);

        $this->assertSame(
            FollowUpReminderStatus::Pending->badgeClasses(),
            $followUp->statusBadgeClasses(),
        );
    }

    public function test_can_send_sequence_email_only_for_pending_sequence_on_contact_sent(): void
    {
        $contactSent = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $meetingScheduled = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::MeetingScheduled,
                            ]);
        $sendable = FollowUp::factory()
                        ->for($contactSent->client)
                        ->sequenceStep(FollowUpSequenceStep::First)
                        ->create([
                            'opportunity_id' => $contactSent->id,
                        ]);
        $manual = FollowUp::factory()
                        ->for($contactSent->client)
                        ->create([
                            'opportunity_id' => $contactSent->id,
                        ]);
        $wrongStage = FollowUp::factory()
                        ->for($meetingScheduled->client)
                        ->sequenceStep(FollowUpSequenceStep::First)
                        ->create([
                            'opportunity_id' => $meetingScheduled->id,
                        ]);
        $completed = FollowUp::factory()
                        ->for($contactSent->client)
                        ->sequenceStep(FollowUpSequenceStep::First)
                        ->completed()
                        ->create([
                            'opportunity_id' => $contactSent->id,
                        ]);

        $this->assertTrue($sendable->canSendSequenceEmail());
        $this->assertFalse($manual->canSendSequenceEmail());
        $this->assertFalse($wrongStage->canSendSequenceEmail());
        $this->assertFalse($completed->canSendSequenceEmail());
    }
}
