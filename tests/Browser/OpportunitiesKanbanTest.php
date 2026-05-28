<?php

use App\Enums\OpportunityStage;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;

it('displays the kanban board with stage columns', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'client_id' => $client->id,
        'title' => 'Kanban Card',
        'stage' => OpportunityStage::Lead,
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->assertNoSmoke()
        ->assertPresent('[data-test="opportunities-page"]')
        ->assertPresent('[data-test="kanban-column-lead"]')
        ->assertPresent('[data-test="opportunity-card-'.$opportunity->id.'"]')
        ->assertSee('Kanban Card');
});

it('moves an opportunity to another stage via the card menu', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'stage' => OpportunityStage::Lead,
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('[data-test="opportunity-card-'.$opportunity->id.'"] [data-test="opportunity-move-stage"]')
        ->click('Won')
        ->assertPresent('[data-test="kanban-column-won"] [data-test="opportunity-card-'.$opportunity->id.'"]');

    expect($opportunity->fresh()->stage)->toBe(OpportunityStage::Won);
});
