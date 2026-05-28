<?php

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_soft_deleted_when_no_opportunities_exist(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        app(ClientService::class)->delete($client);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_client_can_be_deleted_via_livewire(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->call('openDeleteModal', $client->id)
            ->call('deleteClient')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}
