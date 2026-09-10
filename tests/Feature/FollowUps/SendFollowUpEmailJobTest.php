<?php

namespace Tests\Feature\FollowUps;

use App\Ai\Agents\WriteFollowUpEmailAgent;
use App\Enums\FollowUpReminderStatus;
use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Events\ContactWithFollowUp;
use App\Jobs\SendFollowUpEmailJob;
use App\Mail\FirstContactOutreachMail;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendFollowUpEmailJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_follow_up_one_writes_note_sends_mail_and_creates_second_reminder(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-09-09 13:05:00');

        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create([
                            'contact_email' => 'sarah@greensprout.test',
                            'company_name' => 'GreenSprout Lawn Care',
                        ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($client)
                        ->create([
                            'opportunity_id' => $opportunity->id,
                            'sequence_step' => 1,
                        ]);
        $copySubject = '🔁 A new way to turn local quotes into booked work';
        $copyBody = "Hi Sarah,\n\nHere is a new insight on the same problem.\n\nRoger Pereira\n[Front Porch Creative](https://frontporchcreative.io)";

        SendFollowUpEmailJob::dispatchSync($followUp->id, $user->id);

        $followUp->refresh();
        $expectedDueAt = Carbon::parse('2026-09-12 09:00:00');
        $nextFollowUp = FollowUp::where('opportunity_id', $opportunity->id)
                            ->where('id', '!=', $followUp->id)
                            ->first();
        $emailNote = OpportunityNote::where('opportunity_id', $opportunity->id)
                            ->where('body', 'like', '%'.$copySubject.'%')
                            ->first();
        $statusNote = OpportunityNote::where('opportunity_id', $opportunity->id)
                            ->where('body', 'Follow-up 1 sent')
                            ->first();

        $this->assertSame(FollowUpReminderStatus::Completed, $followUp->reminder_status);
        $this->assertNotNull($emailNote);
        $this->assertSame($user->id, $emailNote->user_id);
        $this->assertStringContainsString($copyBody, $emailNote->body);
        $this->assertNotNull($statusNote);
        $this->assertNotNull($nextFollowUp);
        $this->assertSame(2, $nextFollowUp->sequence_step);
        $this->assertSame(FollowUpReminderStatus::Pending, $nextFollowUp->reminder_status);
        $this->assertTrue($expectedDueAt->equalTo($nextFollowUp->due_at));

        $opportunity->refresh();

        $this->assertSame(PipelineStage::ContactSent, $opportunity->stage);

        Mail::assertSent(FirstContactOutreachMail::class, function (FirstContactOutreachMail $mail) use ($client, $copySubject, $copyBody): bool {
            $hasRecipient = $mail->hasTo($client->contact_email);
            $hasSubject = $mail->emailSubject === $copySubject;
            $hasBody = $mail->markdownBody === $copyBody;

            if (! $hasRecipient) {
                return false;
            }

            if (! $hasSubject) {
                return false;
            }

            return $hasBody;
        });
        WriteFollowUpEmailAgent::assertPrompted(function ($prompt) use ($client): bool {
            $promptText = $prompt->prompt;
            $hasCompany = str_contains($promptText, $client->company_name);
            $hasSequenceStep = str_contains($promptText, '"sequence_step":1');
            $hasPreviousEmails = str_contains($promptText, 'previous_emails');
            $hasIntroductionSubject = str_contains($promptText, 'A simple way to bring in more local conversations');
            $hasRePrefix = str_contains($promptText, '"Re:');

            if ($hasCompany === false) {
                return false;
            }

            if ($hasSequenceStep === false) {
                return false;
            }

            if ($hasPreviousEmails === false) {
                return false;
            }

            if ($hasIntroductionSubject === false) {
                return false;
            }

            return $hasRePrefix === false;
        });
    }

    public function test_follow_up_two_sends_last_email_and_moves_to_no_response(): void
    {
        Mail::fake();
        Event::fake([ContactWithFollowUp::class]);
        WriteFollowUpEmailAgent::fake([
            [
                'channel' => 'email',
                'subject' => '📌 Last note on turning quotes into booked work',
                'body' => "Hi Sarah,\n\nThis is the last email I will send about this.\n\nRoger Pereira\n[Front Porch Creative](https://frontporchcreative.io)",
            ],
        ]);

        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create([
                            'contact_email' => 'sarah@greensprout.test',
                        ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($client)
                        ->create([
                            'opportunity_id' => $opportunity->id,
                            'sequence_step' => 2,
                        ]);
        $copySubject = '📌 Last note on turning quotes into booked work';

        SendFollowUpEmailJob::dispatchSync($followUp->id, $user->id);

        $followUp->refresh();
        $opportunity->refresh();
        $sequenceReminders = FollowUp::where('opportunity_id', $opportunity->id)
                                ->whereNotNull('sequence_step')
                                ->count();
        $statusNote = OpportunityNote::where('opportunity_id', $opportunity->id)
                            ->where('body', 'Follow-up 2 sent')
                            ->first();
        $emailNote = OpportunityNote::where('opportunity_id', $opportunity->id)
                            ->where('body', 'like', '%'.$copySubject.'%')
                            ->first();

        $this->assertSame(FollowUpReminderStatus::Completed, $followUp->reminder_status);
        $this->assertSame(PipelineStage::NoResponse, $opportunity->stage);
        $this->assertSame(OpportunityStatus::Lost, $opportunity->status);
        $this->assertSame(1, $sequenceReminders);
        $this->assertNotNull($statusNote);
        $this->assertNotNull($emailNote);
        Event::assertNotDispatched(ContactWithFollowUp::class);
        Mail::assertSent(FirstContactOutreachMail::class);
        WriteFollowUpEmailAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;
            $hasStep = str_contains($promptText, '"sequence_step":2');
            $hasIntroductionSubject = str_contains($promptText, 'A simple way to bring in more local conversations');

            if ($hasStep === false) {
                return false;
            }

            return $hasIntroductionSubject;
        });
    }

    public function test_skips_send_when_opportunity_is_not_contact_sent(): void
    {
        Mail::fake();

        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::MeetingScheduled,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($opportunity->client)
                        ->create([
                            'opportunity_id' => $opportunity->id,
                            'sequence_step' => 1,
                        ]);

        SendFollowUpEmailJob::dispatchSync($followUp->id, null);

        $followUp->refresh();
        $opportunity->refresh();

        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame(PipelineStage::MeetingScheduled, $opportunity->stage);
        Mail::assertNothingSent();
    }

    public function test_smtp_failure_leaves_reminder_pending_and_stage_unchanged(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('SMTP failed'));

        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($opportunity->client)
                        ->create([
                            'opportunity_id' => $opportunity->id,
                            'sequence_step' => 1,
                        ]);

        try {
            SendFollowUpEmailJob::dispatchSync($followUp->id, null);
            $this->fail('Expected SMTP failure to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('SMTP failed', $exception->getMessage());
        }

        $followUp->refresh();
        $opportunity->refresh();

        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame(PipelineStage::ContactSent, $opportunity->stage);
        $this->assertSame(0, OpportunityNote::where('opportunity_id', $opportunity->id)->count());
        $this->assertSame(1, FollowUp::where('opportunity_id', $opportunity->id)->count());
    }

    public function test_empty_copywriter_email_is_incomplete(): void
    {
        WriteFollowUpEmailAgent::fake([
            [
                'channel' => 'email',
                'subject' => '',
                'body' => '',
            ],
        ]);

        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($opportunity->client)
                        ->create([
                            'opportunity_id' => $opportunity->id,
                            'sequence_step' => 1,
                        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Follow-up email output was incomplete.');

        SendFollowUpEmailJob::dispatchSync($followUp->id, null);
    }

    public function test_skips_when_sequence_step_is_null(): void
    {
        Mail::fake();

        $opportunity = Opportunity::factory()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($opportunity->client)
                        ->create([
                            'opportunity_id' => $opportunity->id,
                        ]);

        SendFollowUpEmailJob::dispatchSync($followUp->id, null);

        Mail::assertNothingSent();
    }

    public function test_skips_when_the_follow_up_has_no_opportunity(): void
    {
        Mail::fake();

        $followUp = FollowUp::factory()
                        ->create([
                            'opportunity_id' => null,
                            'sequence_step' => 1,
                        ]);

        SendFollowUpEmailJob::dispatchSync($followUp->id, null);

        Mail::assertNothingSent();
    }

    public function test_still_sends_when_the_reminder_was_completed_on_click(): void
    {
        Mail::fake();

        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'stage' => PipelineStage::ContactSent,
                            ]);
        $followUp = FollowUp::factory()
                        ->for($opportunity->client)
                        ->completed()
                        ->create([
                            'opportunity_id' => $opportunity->id,
                            'sequence_step' => 1,
                        ]);

        SendFollowUpEmailJob::dispatchSync($followUp->id, null);

        Mail::assertSent(FirstContactOutreachMail::class);
    }
}
