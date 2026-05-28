<?php

namespace Tests\Feature\Clients;

use App\Enums\ClientStatus;
use App\Livewire\Leads\Index;
use App\Models\Client;
use App\Models\ClientAiInsight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientLivewireActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ignore_and_contact_intent_actions(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('ignoreClient', $client->id);

        $this->assertSame(ClientStatus::Ignored, $client->fresh()->status);

        Livewire::test(Index::class)
            ->call('markContactIntent', $client->id);

        $this->assertSame(ClientStatus::ContactIntent, $client->fresh()->status);
    }

    public function test_detail_modal_shows_ai_summary_when_present(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        ClientAiInsight::factory()->for($client)->create([
            'summary' => 'Strong fit for enterprise tier.',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openDetailModal', $client->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('Strong fit for enterprise tier.');
    }

    public function test_form_modal_contact_rows_can_be_added_and_removed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openCreateModal')
            ->call('addContactRow')
            ->assertCount('contacts', 2)
            ->call('removeContactRow', 1)
            ->assertCount('contacts', 1);
    }
}
