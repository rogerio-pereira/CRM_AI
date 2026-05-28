<?php

namespace Tests\Unit\Models;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopes_filter_by_status(): void
    {
        Client::factory()->create(['status' => ClientStatus::Active]);
        Client::factory()->archived()->create();
        Client::factory()->ignored()->create();

        $this->assertSame(1, Client::query()->active()->count());
        $this->assertSame(1, Client::query()->archived()->count());
        $this->assertSame(1, Client::query()->ignored()->count());
    }
}
