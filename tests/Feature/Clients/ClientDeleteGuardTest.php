<?php

namespace Tests\Feature\Clients;

use App\Http\Controllers\ClientController;
use App\Livewire\Leads\Index;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        app(ClientController::class)->destroy($client);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_client_can_be_deleted_via_livewire(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id)
            ->call('deleteClient')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}
