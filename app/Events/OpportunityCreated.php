<?php

namespace App\Events;

use App\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Opportunity $opportunity) {}
}
