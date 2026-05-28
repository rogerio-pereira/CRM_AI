<?php

namespace Tests\Feature\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_archived_via_service(): void
    {
        $client = Client::factory()->create();

        app(ClientService::class)->archive($client);

        $this->assertSame(ClientStatus::Archived, $client->fresh()->status);
    }

    public function test_client_can_be_ignored_via_service(): void
    {
        $client = Client::factory()->create();

        app(ClientService::class)->ignore($client);

        $this->assertSame(ClientStatus::Ignored, $client->fresh()->status);
    }

    public function test_client_can_be_marked_as_contact_intent_via_service(): void
    {
        $client = Client::factory()->create();

        app(ClientService::class)->markContactIntent($client);

        $this->assertSame(ClientStatus::ContactIntent, $client->fresh()->status);
    }

    public function test_status_filter_shows_only_matching_clients(): void
    {
        $user = User::factory()->create();

        Client::factory()->create(['company_name' => 'Active Co']);
        Client::factory()->archived()->create(['company_name' => 'Archived Co']);

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->set('statusFilter', ClientStatus::Archived->value)
            ->assertSee('Archived Co')
            ->assertDontSee('Active Co');
    }

    public function test_archive_action_updates_status_via_livewire(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->call('archiveClient', $client->id);

        $this->assertSame(ClientStatus::Archived, $client->fresh()->status);
    }
}
