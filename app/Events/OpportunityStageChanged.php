<?php

namespace App\Events;

use App\Enums\OpportunityStage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $opportunityId,
        public OpportunityStage $from,
        public OpportunityStage $to,
        public int $userId,
    ) {}
}
