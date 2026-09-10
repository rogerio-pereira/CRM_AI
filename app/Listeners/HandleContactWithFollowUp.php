<?php

namespace App\Listeners;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpSequenceStep;
use App\Enums\PipelineStage;
use App\Events\ContactWithFollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Services\FollowUpService;
use App\Services\OpportunityService;
use Carbon\Carbon;

/*
 * Called by app/Events/ContactWithFollowUp.php
 */
class HandleContactWithFollowUp
{
    public function __construct(
        private readonly OpportunityService $opportunities,
        private readonly FollowUpService $followUps,
    ) {}

    public function handle(ContactWithFollowUp $event): void
    {
        $opportunity = $event->opportunity;
        $userId = $event->userId;
        $sentStep = $event->sentStep;

        if ($sentStep === ContactWithFollowUp::INTRODUCTION_STEP) {
            $this->moveToContactSent($opportunity, $userId);
            $this->recordFirstEmailNote($opportunity, $userId);
            $this->createSequenceReminder(
                $opportunity,
                FollowUpSequenceStep::First,
            );

            return;
        }

        if ($sentStep === ContactWithFollowUp::FOLLOW_UP_ONE_STEP) {
            $this->recordFollowUpOneSentNote($opportunity, $userId);
            $this->createSequenceReminder(
                $opportunity,
                FollowUpSequenceStep::Second,
            );
        }
    }

    private function moveToContactSent(Opportunity $opportunity, ?int $userId): void
    {
        $targetStage = PipelineStage::ContactSent;

        $this->opportunities
            ->moveToStage(
                $opportunity,
                $targetStage,
                $userId,
            );
    }

    private function createSequenceReminder(
        Opportunity $opportunity,
        FollowUpSequenceStep $sequenceStep,
    ): void {
        $dueAt = Carbon::now()
                    ->addDays(3)
                    ->setTime(9, 0);
        $priority = FollowUpPriority::Medium;
        $notes = $this->reminderNotes($sequenceStep);

        $this->followUps
            ->create([
                'client_id' => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'sequence_step' => $sequenceStep,
                'due_at' => $dueAt,
                'priority' => $priority,
                'notes' => $notes,
            ]);
    }

    private function reminderNotes(FollowUpSequenceStep $sequenceStep): string
    {
        if ($sequenceStep === FollowUpSequenceStep::First) {
            return __('Follow up after first-contact email.');
        }

        return __('Send the last follow-up email.');
    }

    private function recordFirstEmailNote(Opportunity $opportunity, ?int $userId): void
    {
        $noteBody = __('First Email sent');
        $noteAttributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => $userId,
            'body' => $noteBody,
        ];
        OpportunityNote::create($noteAttributes);
    }

    private function recordFollowUpOneSentNote(Opportunity $opportunity, ?int $userId): void
    {
        $noteBody = __('Follow-up 1 sent');
        $noteAttributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => $userId,
            'body' => $noteBody,
        ];
        OpportunityNote::create($noteAttributes);
    }
}
