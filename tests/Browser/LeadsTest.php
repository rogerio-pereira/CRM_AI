<?php

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;

it('displays the leads page and creates a lead', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/leads')
        ->assertNoSmoke()
        ->assertPresent('[data-test="leads-page"]')
        ->assertSee('Leads / Clients')
        ->click('@leads-create-button')
        ->fill('@leads-form-company-name', 'Browser Test Co')
        ->fill('@leads-form-contact-email', 'hello@browsertest.test')
        ->fill('@leads-form-website', 'https://browsertest.test')
        ->click('@leads-form-submit')
        ->assertSee('Browser Test Co');

    expect(Client::query()->where('company_name', 'Browser Test Co')->exists())->toBeTrue();
});

it('opens detail modal and archives a lead from the actions menu', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'company_name' => 'Detail Modal Co',
        'qualification_notes' => 'Initial notes',
    ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->assertSee('Detail Modal Co')
        ->assertSee('Initial notes')
        ->click('@leads-detail-close')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-archive-'.$client->id)
        ->assertSee('Detail Modal Co');

    $client->refresh();

    expect($client->status)->toBe(ClientStatus::Archived);
});

it('filters leads by archived status', function () {
    $user = User::factory()->create();
    Client::factory()->create(['company_name' => 'Visible Active Co']);
    Client::factory()->archived()->create(['company_name' => 'Hidden Archived Co']);

    $this->actingAs($user);

    visit('/leads')
        ->assertSee('Visible Active Co')
        ->assertSee('Hidden Archived Co')
        ->select('@leads-status-filter', ClientStatus::Archived->value)
        ->assertSee('Hidden Archived Co')
        ->assertDontSee('Visible Active Co');
});
