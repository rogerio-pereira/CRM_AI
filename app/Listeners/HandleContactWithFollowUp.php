<?php

namespace App\Listeners;

use App\Enums\FollowUpPriority;
use App\Enums\PipelineStage;
use App\Events\ContactWithFollowUp;
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

        $dueAt = Carbon::now()
                    ->addDays(3)
                    ->setTime(9, 0);

        $this->followUps
            ->create([
                'client_id' => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'due_at' => $dueAt,
                'priority' => FollowUpPriority::Medium,
                'notes' => __('Follow up after first-contact email.'),
            ]);
    }
}
