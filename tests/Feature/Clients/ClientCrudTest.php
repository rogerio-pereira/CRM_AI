<?php

namespace Tests\Feature\Clients;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee(__('Leads / Clients'));
    }

    public function test_client_can_be_created_via_livewire(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->call('openCreateModal')
            ->set('company_name', 'Acme Corp')
            ->set('lead_source', 'Referral')
            ->set('contacts.0.name', 'Jane Doe')
            ->set('contacts.0.email', 'jane@acme.test')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'company_name' => 'Acme Corp',
            'lead_source' => 'Referral',
            'status' => ClientStatus::Active->value,
        ]);
    }

    public function test_client_can_be_updated_via_livewire(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'company_name' => 'Old Name',
        ]);

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->call('openEditModal', $client->id)
            ->set('company_name', 'New Name')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'New Name',
        ]);
    }

    public function test_company_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->call('openCreateModal')
            ->set('company_name', '')
            ->call('saveClient')
            ->assertHasErrors(['company_name']);
    }

    public function test_website_must_be_a_valid_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->call('openCreateModal')
            ->set('company_name', 'Acme Corp')
            ->set('website', 'not-a-url')
            ->call('saveClient')
            ->assertHasErrors(['website']);
    }

    public function test_search_filters_clients_by_company_name(): void
    {
        $user = User::factory()->create();

        Client::factory()->create(['company_name' => 'Alpha Industries']);
        Client::factory()->create(['company_name' => 'Beta Solutions']);

        $this->actingAs($user);

        Livewire::test('pages::leads.index')
            ->set('search', 'Alpha')
            ->assertSee('Alpha Industries')
            ->assertDontSee('Beta Solutions');
    }
}
