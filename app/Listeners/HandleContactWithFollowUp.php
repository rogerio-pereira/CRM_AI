<?php

namespace App\Listeners;

use App\Enums\FollowUpPriority;
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

        if ($event->sentStep === 0) {
            $this->opportunities
                ->moveToStage(
                    $opportunity,
                    PipelineStage::ContactSent,
                    $userId,
                );
            OpportunityNote::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => $userId,
                'body' => __('First Email sent'),
            ]);
            $this->createReminder(
                $opportunity,
                1,
                __('Follow up after first-contact email.'),
            );

            return;
        }

        if ($event->sentStep === 1) {
            OpportunityNote::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => $userId,
                'body' => __('Follow-up 1 sent'),
            ]);
            $this->createReminder(
                $opportunity,
                2,
                __('Send the last follow-up email.'),
            );
        }
    }

    private function createReminder(
        Opportunity $opportunity,
        int $sequenceStep,
        string $notes,
    ): void {
        $dueAt = Carbon::now()
                    ->addDays(3)
                    ->setTime(9, 0);

        $this->followUps
            ->create([
                'client_id' => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'sequence_step' => $sequenceStep,
                'due_at' => $dueAt,
                'priority' => FollowUpPriority::Medium,
                'notes' => $notes,
            ]);
    }
}
