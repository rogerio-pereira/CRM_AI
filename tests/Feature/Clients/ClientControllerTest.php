<?php

namespace Tests\Feature\Clients;

use App\Enums\ClientStatus;
use App\Http\Controllers\ClientController;
use App\Models\Client;
use App\Models\ClientAiInsight;
use App\Models\ClientContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_replaces_contacts_and_creates_ai_insight_placeholder(): void
    {
        $controller = app(ClientController::class);

        $client = $controller->store([
            'company_name' => 'Stored Co',
            'contacts' => [
                ['name' => 'Alice', 'email' => 'alice@test.com', 'phone' => null],
                ['name' => '', 'email' => null, 'phone' => null],
            ],
            'social_links' => ['linkedin' => 'https://linkedin.com/company/stored'],
        ]);

        $this->assertDatabaseCount('client_contacts', 1);
        $this->assertDatabaseHas('client_contacts', [
            'client_id' => $client->id,
            'name' => 'Alice',
        ]);
        $this->assertDatabaseHas('client_ai_insights', [
            'client_id' => $client->id,
        ]);
    }

    public function test_update_syncs_contacts(): void
    {
        $client = Client::factory()->create();
        ClientContact::factory()->for($client)->create(['name' => 'Old Contact']);
        ClientAiInsight::factory()->for($client)->create();

        app(ClientController::class)->update($client, [
            'company_name' => $client->company_name,
            'contacts' => [
                ['name' => 'New Contact', 'email' => null, 'phone' => null],
            ],
            'social_links' => [],
        ]);

        $this->assertDatabaseMissing('client_contacts', ['name' => 'Old Contact']);
        $this->assertDatabaseHas('client_contacts', ['name' => 'New Contact']);
    }

    public function test_paginate_filters_by_status(): void
    {
        Client::factory()->create(['company_name' => 'Active']);
        Client::factory()->archived()->create(['company_name' => 'Archived']);

        $results = app(ClientController::class)->paginate(status: ClientStatus::Archived);

        $this->assertCount(1, $results->items());
        $this->assertSame('Archived', $results->items()[0]->company_name);
    }

    public function test_paginate_filters_by_search(): void
    {
        Client::factory()->create(['company_name' => 'Acme Global']);
        Client::factory()->create(['company_name' => 'Other Ltd']);

        $results = app(ClientController::class)->paginate(search: 'acme');

        $this->assertCount(1, $results->items());
        $this->assertSame('Acme Global', $results->items()[0]->company_name);
    }
}
