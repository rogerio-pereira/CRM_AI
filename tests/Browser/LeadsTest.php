<?php

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\Task;
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

    $client = Client::where('company_name', 'Browser Test Co')->first();

    expect($client)->not->toBeNull();
});

it('opens detail modal and archives a lead from the actions menu', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'company_name' => 'Detail Modal Co',
        'qualification_notes' => 'Initial notes',
        'website' => 'https://detail-modal.test',
    ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->assertPresent('[data-test="leads-detail-status-badge"][data-status="active"]')
        ->assertSee('Detail Modal Co')
        ->assertSee('Initial notes')
        ->assertPresent('[data-test="leads-detail-website-link"]')
        ->assertSee('https://detail-modal.test')
        ->click('@leads-detail-close')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-archive-'.$client->id)
        ->assertSee('Detail Modal Co');

    $client->refresh();

    expect($client->status)->toBe(ClientStatus::Archived);

    visit('/leads')
        ->assertPresent('[data-test="leads-status-badge-'.$client->id.'"][data-status="archived"]');
});

it('opens the lead detail modal with related opportunity AI recommendations', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'company_name' => 'Lead Recommendations Co',
    ]);
    $opportunity = Opportunity::factory()
        ->for($client)
        ->qualificationQualified()
        ->withAiInsights()
        ->withAiRecommendations()
        ->create([
            'title' => 'Related AI Deal',
        ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->assertPresent('[data-test="leads-detail-opportunity-'.$opportunity->id.'"]')
        ->assertPresent('[data-test="ai-suggestion-panel"]')
        ->assertPresent('[data-test="opportunities-detail-ai-insights"]')
        ->assertSee('Ready for a first conversation.')
        ->assertSee('Where do most new customers hear about you today?')
        ->assertSee('Review the example email before any outreach')
        ->assertPresent('[data-test="ai-suggestion-refresh"]')
        ->assertSee('AI-generated. Not a confirmed human decision.');
});

it('creates a follow-up from the leads list actions menu', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'List Menu Follow-up Co']);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-create-follow-up-'.$client->id)
        ->assertPresent('[data-test="follow-ups-quick-create-modal"]')
        ->fill('@follow-ups-form-notes', 'Follow-up from list menu')
        ->click('@follow-ups-form-submit')
        ->assertSee('List Menu Follow-up Co');

    expect(FollowUp::where('notes', 'Follow-up from list menu')->exists())->toBeTrue();
});

it('creates a follow-up from the client detail modal', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Detail Follow-up Co']);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->click('@leads-detail-create-follow-up')
        ->assertPresent('[data-test="follow-ups-quick-create-modal"]')
        ->fill('@follow-ups-form-notes', 'Follow-up from client detail')
        ->click('@follow-ups-form-submit')
        ->click('@leads-detail-close')
        ->assertSee('Detail Follow-up Co');

    expect(FollowUp::where('notes', 'Follow-up from client detail')->exists())->toBeTrue();
});

it('creates a task from the leads list actions menu', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'List Menu Task Co']);

    $this->actingAs($user);

    $dueAt = now()->addDay()->format('Y-m-d\TH:i');

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-create-task-'.$client->id)
        ->assertPresent('[data-test="tasks-quick-create-modal"]')
        ->fill('@tasks-quick-form-title', 'Task from list menu')
        ->fill('@tasks-quick-form-due-at', $dueAt)
        ->click('@tasks-quick-form-submit')
        ->assertSee('List Menu Task Co');

    expect(Task::where('title', 'Task from list menu')->exists())->toBeTrue();
});

it('creates a task from the client detail modal', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Detail Task Co']);

    $this->actingAs($user);

    $dueAt = now()->addDay()->format('Y-m-d\TH:i');

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->click('@leads-detail-create-task')
        ->assertPresent('[data-test="tasks-quick-create-modal"]')
        ->fill('@tasks-quick-form-title', 'Task from client detail')
        ->fill('@tasks-quick-form-due-at', $dueAt)
        ->click('@tasks-quick-form-submit')
        ->click('@leads-detail-close')
        ->assertSee('Detail Task Co');

    expect(Task::where('title', 'Task from client detail')->exists())->toBeTrue();
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
