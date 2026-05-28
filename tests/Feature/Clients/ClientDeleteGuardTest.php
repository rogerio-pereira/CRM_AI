<?php

namespace Tests\Feature\Clients;

use App\Enums\OpportunityStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_client_delete_is_blocked_when_open_opportunity_exists(): void
    {
        $client = Client::factory()->create();
        Opportunity::factory()->create([
            'client_id' => $client->id,
            'stage' => OpportunityStage::Contact,
        ]);

        $this->expectException(ValidationException::class);

        app(ClientService::class)->delete($client);
    }

    public function test_client_can_be_deleted_when_only_terminal_opportunities_exist(): void
    {
        $client = Client::factory()->create();
        Opportunity::factory()->won()->create(['client_id' => $client->id]);

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
