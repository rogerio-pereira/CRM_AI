<?php

namespace Tests\Feature\Leads;

use App\Enums\ClientStatus;
use App\Livewire\Leads\Index;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_index_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk();
    }

    public function test_user_can_create_a_lead(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', 'Acme Corp')
            ->set('contact_email', 'sales@acme.test')
            ->set('website', 'https://acme.test')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'company_name' => 'Acme Corp',
            'contact_email' => 'sales@acme.test',
            'website' => 'https://acme.test',
            'status' => ClientStatus::Active->value,
        ]);
    }

    public function test_company_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', '')
            ->call('saveClient')
            ->assertHasErrors(['company_name']);
    }

    public function test_website_must_be_a_valid_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', 'Acme Corp')
            ->set('website', 'not-a-url')
            ->call('saveClient')
            ->assertHasErrors(['website']);
    }

    public function test_user_can_update_a_lead(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'Old Name']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openEditModal', $client->id)
            ->set('company_name', 'New Name')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'New Name',
        ]);
    }

    public function test_user_can_archive_a_lead(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setArchived', $client->id);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => ClientStatus::Archived->value,
        ]);
    }

    public function test_user_can_ignore_a_lead(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setIgnored', $client->id);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => ClientStatus::Ignored->value,
        ]);
    }

    public function test_user_can_mark_contact_intent(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setContactIntent', $client->id);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => ClientStatus::ContactIntent->value,
        ]);
    }

    public function test_status_filter_limits_listed_clients(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['company_name' => 'Active Co']);
        Client::factory()->archived()->create(['company_name' => 'Archived Co']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->set('statusFilter', ClientStatus::Archived->value);

        $names = $component->instance()->clients->pluck('company_name')->all();

        $this->assertSame(['Archived Co'], $names);
    }

    public function test_search_filters_by_company_name(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['company_name' => 'Alpha Industries']);
        Client::factory()->create(['company_name' => 'Beta LLC']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->set('search', 'alpha');

        $names = $component->instance()->clients->pluck('company_name')->all();

        $this->assertSame(['Alpha Industries'], $names);
    }

    public function test_user_cannot_delete_client_with_open_opportunity(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Opportunity::factory()->for($client)->open()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id)
            ->call('confirmDelete')
            ->assertHasErrors(['delete']);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_user_can_delete_client_when_opportunities_are_terminal(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Opportunity::factory()->for($client)->won()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id)
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}
