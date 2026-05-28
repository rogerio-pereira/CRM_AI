<?php

namespace Tests\Feature\Opportunities;

use App\Enums\OpportunityStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpportunityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunities_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('opportunities.index'))
            ->assertOk()
            ->assertSee(__('Opportunities'));
    }

    public function test_opportunity_can_be_created_via_livewire(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::opportunities.index')
            ->call('openCreateModal')
            ->set('opportunityTitle', 'Enterprise deal')
            ->set('client_id', $client->id)
            ->set('estimated_value', '50000')
            ->call('saveOpportunity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'title' => 'Enterprise deal',
            'client_id' => $client->id,
            'stage' => OpportunityStage::Lead->value,
        ]);
    }

    public function test_opportunity_can_be_updated_via_livewire(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create(['title' => 'Old title']);

        $this->actingAs($user);

        Livewire::test('pages::opportunities.index')
            ->call('openEditModal', $opportunity->id)
            ->set('opportunityTitle', 'New title')
            ->call('saveOpportunity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'title' => 'New title',
        ]);
    }

    public function test_title_is_required(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::opportunities.index')
            ->call('openCreateModal')
            ->set('opportunityTitle', '')
            ->set('client_id', $client->id)
            ->call('saveOpportunity')
            ->assertHasErrors(['opportunityTitle']);
    }
}
