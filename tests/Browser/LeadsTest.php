<?php

use App\Models\Client;
use App\Models\User;

it('creates a lead from the leads page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/leads')
        ->assertNoSmoke()
        ->assertPresent('[data-test="leads-page"]')
        ->click('[data-test="leads-create-button"]')
        ->assertPresent('[data-test="leads-form-modal"]')
        ->fill('company_name', 'Browser Test Co')
        ->fill('lead_source', 'Event')
        ->click('[data-test="leads-form-submit"]')
        ->assertSee('Browser Test Co');

    expect(Client::query()->where('company_name', 'Browser Test Co')->exists())->toBeTrue();
});

it('opens the detail modal with placeholder sections', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Detail Modal Co']);

    $this->actingAs($user);

    visit('/leads')
        ->assertNoSmoke()
        ->click('[data-test="leads-row-'.$client->id.'"]')
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->assertPresent('[data-test="client-ai-insights-placeholder"]')
        ->assertPresent('[data-test="client-opportunity-history"]')
        ->assertSee('Detail Modal Co');
});

it('shows the status filter on the leads page', function () {
    $user = User::factory()->create();

    Client::factory()->create(['company_name' => 'Visible Active']);
    Client::factory()->archived()->create(['company_name' => 'Hidden Archived']);

    $this->actingAs($user);

    visit('/leads')
        ->assertNoSmoke()
        ->assertPresent('[data-test="leads-status-filter"]')
        ->assertSee('Visible Active');
});
