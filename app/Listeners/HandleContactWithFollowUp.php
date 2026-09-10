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
        $this->recordNote(
            $opportunity,
            $userId,
            __('First Email sent'),
        );
        $this->createReminder(
            $opportunity,
            __('Follow up after first-contact email.'),
        );
    }

    private function moveToContactSent(Opportunity $opportunity, ?int $userId): void
    {
        $this->opportunities
            ->moveToStage(
                $opportunity,
                PipelineStage::ContactSent,
                $userId,
            );
    }

    private function recordNote(
        Opportunity $opportunity,
        ?int $userId,
        string $body,
    ): void {
        OpportunityNote::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => $userId,
            'body' => $body,
        ]);
    }

    private function createReminder(Opportunity $opportunity, string $notes): void
    {
        $dueAt = Carbon::now()
                    ->addDays(3)
                    ->setTime(9, 0);

        $this->followUps
            ->create([
                'client_id' => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'due_at' => $dueAt,
                'priority' => FollowUpPriority::Medium,
                'notes' => $notes,
            ]);
    }
}
