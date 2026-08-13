<?php

use App\Enums\ClientStatus;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\FollowUp;
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
    expect($client->qualification_status)->toBe(QualificationStatus::Qualified);

    visit('/leads')
        ->assertPresent('[data-test="leads-qualification-badge-'.$client->id.'"][data-status="qualified"]');
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
        ->assertPresent('[data-test="leads-detail-status-badge"][data-status="active"]')
        ->assertSee('Detail Modal Co')
        ->assertSee('Initial notes')
        ->click('@leads-detail-close')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-archive-'.$client->id)
        ->assertSee('Detail Modal Co');

    $client->refresh();

    expect($client->status)->toBe(ClientStatus::Archived);

    visit('/leads')
        ->assertPresent('[data-test="leads-status-badge-'.$client->id.'"][data-status="archived"]');
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

it('renders qualification status chips for pending processing qualified and failed', function () {
    $user = User::factory()->create();
    $pending = Client::factory()->qualificationPending()->create(['company_name' => 'Pending Chip Browser Co']);
    $processing = Client::factory()->qualificationProcessing()->create(['company_name' => 'Processing Chip Browser Co']);
    $qualified = Client::factory()->qualificationQualified()->create(['company_name' => 'Qualified Chip Browser Co']);
    $failed = Client::factory()->qualificationFailed()->create(['company_name' => 'Failed Chip Browser Co']);

    $this->actingAs($user);

    visit('/leads')
        ->assertPresent('[data-test="leads-qualification-badge-'.$pending->id.'"][data-status="pending"]')
        ->assertPresent('[data-test="leads-qualification-badge-'.$processing->id.'"][data-status="processing"]')
        ->assertPresent('[data-test="leads-qualification-badge-'.$qualified->id.'"][data-status="qualified"]')
        ->assertPresent('[data-test="leads-qualification-badge-'.$failed->id.'"][data-status="failed"]')
        ->click('@leads-actions-'.$failed->id)
        ->click('@leads-view-'.$failed->id)
        ->assertPresent('[data-test="leads-detail-qualification-badge"][data-status="failed"]')
        ->assertPresent('[data-test="leads-detail-qualification-error"]')
        ->assertSee('Qualification could not be completed. The team can try again later.');
});
