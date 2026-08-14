<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/*
 * No listeners registered.
 */
class ClientCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Client $client) {}
}
