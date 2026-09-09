<?php

namespace App\Events;

use App\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
 * @calls app/Listeners/HandleFirstContactOutreachSent
 */
class ContactWithFollowUp
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Opportunity $opportunity,
        public ?int $userId,
    ) {}
}
