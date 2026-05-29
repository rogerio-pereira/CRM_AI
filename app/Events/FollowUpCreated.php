<?php

namespace App\Events;

use App\Models\FollowUp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FollowUpCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public FollowUp $followUp) {}
}
