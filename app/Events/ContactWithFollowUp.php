<?php

namespace App\Events;

use App\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
 * @calls app/Listeners/HandleContactWithFollowUp
 */
class ContactWithFollowUp
{
    use Dispatchable;
    use SerializesModels;

    public const INTRODUCTION_STEP = 0;

    public const FOLLOW_UP_ONE_STEP = 1;

    public function __construct(
        public Opportunity $opportunity,
        public ?int $userId,
        public int $sentStep = self::INTRODUCTION_STEP,
    ) {}
}
