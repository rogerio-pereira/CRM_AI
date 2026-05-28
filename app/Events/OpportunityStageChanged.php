<?php

namespace App\Events;

use App\Enums\PipelineStage;
use App\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Opportunity $opportunity,
        public PipelineStage $fromStage,
        public PipelineStage $toStage,
        public ?int $userId,
    ) {}
}
