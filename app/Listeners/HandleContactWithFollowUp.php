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

        $this->moveToContactSent($opportunity, $userId);
        $this->createFollowUp($opportunity);
        $this->recordFirstEmailNote($opportunity, $userId);
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

    private function createFollowUp(Opportunity $opportunity): void
    {
        $dueAt = Carbon::now()
                    ->addDays(3)
                    ->setTime(9, 0);
        $priority = FollowUpPriority::Medium;
        $notes = __('Follow up after first-contact email.');

        $this->followUps
            ->create([
                'client_id' => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'due_at' => $dueAt,
                'priority' => $priority,
                'notes' => $notes,
            ]);
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
}
