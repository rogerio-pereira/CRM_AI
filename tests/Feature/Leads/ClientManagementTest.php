<?php

namespace Tests\Feature\Leads;

use App\Enums\ClientStatus;
use App\Livewire\Leads\Index;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_index_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk();
    }

    public function test_user_can_create_a_lead(): void
    {
        $user = User::factory()
                    ->create();

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
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', '')
            ->call('saveClient')
            ->assertHasErrors(['company_name']);
    }

    public function test_website_must_be_a_valid_url(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', 'Acme Corp')
            ->set('website', 'not a valid url')
            ->call('saveClient')
            ->assertHasErrors(['website']);
    }

    public function test_website_without_scheme_is_normalized_to_https(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', 'Acme Corp')
            ->set('website', 'acme.com')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'company_name' => 'Acme Corp',
            'website' => 'https://acme.com',
        ]);
    }

    public function test_social_link_without_scheme_is_normalized_to_https(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', 'Social Co')
            ->set('social_links', [
                [
                    'platform' => 'LinkedIn',
                    'url' => 'linkedin.com/company/social-co',
                ],
            ])
            ->call('saveClient')
            ->assertHasNoErrors();

        $client = Client::where('company_name', 'Social Co')
                        ->first();

        $this->assertNotNull($client);

        $socialLinks = $client->social_links;
        $firstSocialLinkUrl = $socialLinks[0]['url'] ?? null;

        $this->assertSame('https://linkedin.com/company/social-co', $firstSocialLinkUrl);
    }

    public function test_user_can_mark_client_as_active(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->archived()
                        ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setActive', $client->id);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => ClientStatus::Active->value,
        ]);
    }

    public function test_user_can_update_a_lead(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create(['company_name' => 'Old Name']);

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
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

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
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

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
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

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
        $user = User::factory()
                    ->create();

        Client::factory()
                ->create(['company_name' => 'Active Co']);
        Client::factory()
                ->archived()
                ->create(['company_name' => 'Archived Co']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->set('statusFilter', ClientStatus::Archived->value);

        $leadsIndex = $component->instance();
        $clients = $leadsIndex->clients;
        $names = $clients->pluck('company_name')
                    ->all();

        $this->assertSame(['Archived Co'], $names);
    }

    public function test_search_filters_by_company_name(): void
    {
        $user = User::factory()
                    ->create();

        Client::factory()
                ->create(['company_name' => 'Alpha Industries']);
        Client::factory()
                ->create(['company_name' => 'Beta LLC']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->set('search', 'alpha');

        $leadsIndex = $component->instance();
        $clients = $leadsIndex->clients;
        $names = $clients->pluck('company_name')
                    ->all();

        $this->assertSame(['Alpha Industries'], $names);
    }

    public function test_user_cannot_delete_client_with_open_opportunity(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

        Opportunity::factory()
            ->for($client)
            ->open()
            ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id)
            ->call('confirmDelete')
            ->assertHasErrors(['delete']);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_user_can_delete_client_when_opportunities_are_terminal(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

        Opportunity::factory()
            ->for($client)
            ->won()
            ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id)
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_user_can_delete_client_without_opportunities(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id)
            ->call('confirmDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_confirm_delete_does_nothing_when_delete_client_id_is_null(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('confirmDelete');

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_detail_and_delete_computed_properties_are_null_by_default(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $component = Livewire::test(Index::class);
        $leadsIndex = $component->instance();

        $this->assertNull($leadsIndex->detailClient);
        $this->assertNull($leadsIndex->deleteClient);
    }

    public function test_open_detail_modal_loads_client_with_opportunities(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create(['company_name' => 'Detail Co']);

        Opportunity::factory()
            ->for($client)
            ->create(['title' => 'Big deal']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->call('openDetailModal', $client->id);

        $leadsIndex = $component->instance();
        $detailClient = $leadsIndex->detailClient;

        $this->assertSame($client->id, $component->get('detailClientId'));
        $this->assertTrue($component->get('showDetailModal'));
        $this->assertSame('Detail Co', $detailClient?->company_name);
        $this->assertCount(1, $detailClient?->opportunities ?? []);
    }

    public function test_detail_modal_renders_website_as_a_link(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create([
                            'company_name' => 'Website Link Co',
                            'website' => 'https://website-link.test',
                        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-website-link"')
            ->assertSeeHtml('href="https://website-link.test"')
            ->assertSee('https://website-link.test');
    }

    public function test_open_edit_modal_with_null_social_links_initializes_empty_row(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create([
                            'company_name' => 'No Social Co',
                            'social_links' => null,
                        ]);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->call('openEditModal', $client->id);

        $this->assertSame([
            ['platform' => '', 'url' => ''],
        ], $component->get('social_links'));
    }

    public function test_open_edit_modal_with_existing_social_links(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create([
                            'social_links' => [
                                ['platform' => 'Twitter', 'url' => 'https://twitter.com/acme'],
                        ],
                        ]);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->call('openEditModal', $client->id);

        $this->assertSame([
            ['platform' => 'Twitter', 'url' => 'https://twitter.com/acme'],
        ], $component->get('social_links'));
    }

    public function test_add_and_remove_social_link_rows(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->call('openCreateModal')
            ->call('addSocialLinkRow')
            ->call('removeSocialLinkRow', 99);

        $this->assertCount(2, $component->get('social_links'));

        $component->call('removeSocialLinkRow', 0);

        $this->assertCount(1, $component->get('social_links'));
    }

    public function test_save_client_omits_blank_social_link_rows(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->set('company_name', 'Sparse Social Co')
            ->set('social_links', [
                ['platform' => '', 'url' => ''],
                ['platform' => 'Blog', 'url' => 'blog.example.com'],
            ])
            ->call('saveClient')
            ->assertHasNoErrors();

        $client = Client::where('company_name', 'Sparse Social Co')
                        ->first();

        $this->assertNotNull($client);

        $socialLinks = $client->social_links;

        $this->assertCount(1, $socialLinks);
        $this->assertSame('Blog', $socialLinks[0]['platform']);
    }

    public function test_open_delete_modal_sets_delete_client_computed(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create(['company_name' => 'Delete Me Co']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->call('openDeleteModal', $client->id);

        $leadsIndex = $component->instance();
        $deleteClient = $leadsIndex->deleteClient;

        $this->assertTrue($component->get('showDeleteModal'));
        $this->assertSame('Delete Me Co', $deleteClient?->company_name);
    }

    public function test_clients_list_is_paginated_with_twenty_per_page(): void
    {
        $user = User::factory()
                    ->create();

        Client::factory()
                ->count(21)
                ->create();

        $this->actingAs($user);

        $component = Livewire::test(Index::class);
        $leadsIndex = $component->instance();
        $firstPageClients = $leadsIndex->clients;

        $this->assertCount(20, $firstPageClients->items());
        $this->assertSame(21, $firstPageClients->total());

        $component->call('gotoPage', 2);

        $secondPageIndex = $component->instance();
        $secondPageClients = $secondPageIndex->clients;

        $this->assertCount(1, $secondPageClients->items());
    }

    public function test_search_filter_resets_pagination_to_first_page(): void
    {
        $user = User::factory()
                    ->create();

        Client::factory()
                ->count(21)
                ->create(['company_name' => 'Paged Co']);

        $this->actingAs($user);

        $component = Livewire::test(Index::class)
            ->call('gotoPage', 2)
            ->set('search', 'Paged');

        $leadsIndex = $component->instance();
        $clients = $leadsIndex->clients;

        $this->assertSame(1, $clients->currentPage());
    }

    public function test_clients_list_queries_database_with_limit(): void
    {
        $user = User::factory()
                    ->create();

        Client::factory()
                ->count(25)
                ->create();

        $this->actingAs($user);

        DB::enableQueryLog();

        Livewire::test(Index::class);

        $queryLog = DB::getQueryLog();
        $sql = collect($queryLog)
                    ->pluck('query')
                    ->implode(' ');
        $lowercaseSql = strtolower($sql);

        $this->assertStringContainsString('limit', $lowercaseSql);
    }

    public function test_clients_list_shows_created_at(): void
    {
        $user = User::factory()
                    ->create();
        $createdAt = now()->subDays(3)->startOfDay();
        $client = Client::factory()
                        ->create([
                            'company_name' => 'Created At Co',
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);

        $this->actingAs($user);

        $expectedDate = $createdAt->format('M j, Y');

        Livewire::test(Index::class)
            ->assertSeeHtml('data-test="leads-created-at-'.$client->id.'"')
            ->assertSee($expectedDate);
    }
}
