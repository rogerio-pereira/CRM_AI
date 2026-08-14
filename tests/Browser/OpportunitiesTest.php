<?php

use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;

it('displays the kanban board and creates an opportunity', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Kanban Browser Co']);

    $this->actingAs($user);

    visit('/opportunities')
        ->assertNoSmoke()
        ->assertPresent('[data-test="opportunities-page"]')
        ->assertPresent('[data-test="kanban-board"]')
        ->assertPresent('[data-test="kanban-column-lead"]')
        ->assertPresent('[data-test="kanban-column-contact"][data-user-action-column="true"]')
        ->assertPresent('[data-test="kanban-column-proposal-analysis"][data-user-action-column="true"]')
        ->assertNotPresent('[data-test="kanban-column-lead"][data-user-action-column="true"]')
        ->click('@opportunities-create-button')
        ->fill('@opportunities-form-title', 'Browser Kanban Deal')
        ->select('@opportunities-form-client', (string) $client->id)
        ->fill('@opportunities-form-value', '25000')
        ->click('@opportunities-form-submit')
        ->assertSee('Browser Kanban Deal');

    expect(Opportunity::where('title', 'Browser Kanban Deal')->exists())->toBeTrue();
});

it('moves an opportunity via the action menu', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->qualificationProcessing()->create([
        'title' => 'Move Menu Deal',
        'stage' => PipelineStage::Lead,
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->assertPresent('[data-test="kanban-card-'.$opportunity->id.'"]')
        ->click('@kanban-card-actions-'.$opportunity->id)
        ->click('@kanban-card-move-'.$opportunity->id.'-qualification')
        ->assertPresent('[data-test="kanban-column-qualification"] [data-test="kanban-card-'.$opportunity->id.'"]');

    $opportunity->refresh();

    expect($opportunity->stage)->toBe(PipelineStage::Qualification);
});

it('opens the opportunity detail modal with client summary', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create([
        'company_name' => 'Detail Summary Co',
        'contact_name' => 'Alex Summary',
        'contact_email' => 'detail@summary.test',
        'contact_phone' => '813-555-0142',
        'website' => 'https://detail-summary.test',
    ]);
    $opportunity = Opportunity::factory()->for($client)->create([
        'title' => 'Detail Modal Deal',
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-modal"]')
        ->assertSee('Detail Modal Deal')
        ->assertSee('Detail Summary Co')
        ->assertSee('Alex Summary')
        ->assertSee('detail@summary.test')
        ->assertSee('813-555-0142')
        ->assertPresent('[data-test="opportunities-detail-website-link"]')
        ->assertSee('https://detail-summary.test')
        ->assertPresent('[data-test="opportunities-detail-qualification-badge"][data-status="pending"]');
});

it('renders qualification status chips on the kanban and failed error on detail', function () {
    $user = User::factory()->create();
    $pending = Opportunity::factory()->qualificationPending()->create([
        'title' => 'Pending Chip Deal',
        'stage' => PipelineStage::Lead,
    ]);
    $processing = Opportunity::factory()->qualificationProcessing()->create([
        'title' => 'Processing Chip Deal',
        'stage' => PipelineStage::Qualification,
    ]);
    $qualified = Opportunity::factory()->qualificationQualified()->create([
        'title' => 'Qualified Chip Deal',
        'stage' => PipelineStage::Contact,
    ]);
    $failed = Opportunity::factory()->qualificationFailed()->create([
        'title' => 'Failed Chip Deal',
        'stage' => PipelineStage::Qualification,
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->assertPresent('[data-test="kanban-card-qualification-badge-'.$pending->id.'"][data-status="'.QualificationStatus::Pending->value.'"]')
        ->assertPresent('[data-test="kanban-card-qualification-badge-'.$processing->id.'"][data-status="'.QualificationStatus::Processing->value.'"]')
        ->assertPresent('[data-test="kanban-card-qualification-badge-'.$qualified->id.'"][data-status="'.QualificationStatus::Qualified->value.'"]')
        ->assertPresent('[data-test="kanban-card-qualification-badge-'.$failed->id.'"][data-status="'.QualificationStatus::Failed->value.'"]')
        ->click('@kanban-card-open-'.$failed->id)
        ->assertPresent('[data-test="opportunities-detail-qualification-badge"][data-status="failed"]')
        ->assertPresent('[data-test="opportunities-detail-qualification-error"]')
        ->assertSee('Qualification could not be completed. The team can try again later.');
});

it('shows horizontal scroll on narrow viewports', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/opportunities')
        ->on()
        ->mobile()
        ->assertPresent('[data-test="kanban-board"]');
});

it('creates a follow-up from the kanban card button', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Kanban Follow Up Co']);
    $opportunity = Opportunity::factory()->for($client)->create([
        'title' => 'Follow Up From Kanban',
        'stage' => PipelineStage::Contact,
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->assertPresent('[data-test="kanban-card-create-follow-up-'.$opportunity->id.'"]')
        ->click('@kanban-card-create-follow-up-'.$opportunity->id)
        ->assertPresent('[data-test="follow-ups-quick-create-modal"]')
        ->fill('@follow-ups-form-notes', 'Scheduled from Kanban card')
        ->click('@follow-ups-form-submit')
        ->assertSee('Follow Up From Kanban');

    expect(FollowUp::where('notes', 'Scheduled from Kanban card')->exists())->toBeTrue();
});

it('creates a task from the kanban card button', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Kanban Task Co']);
    $opportunity = Opportunity::factory()->for($client)->create([
        'title' => 'Task From Kanban',
        'stage' => PipelineStage::Contact,
    ]);

    $this->actingAs($user);

    $dueAt = now()->addDay()->format('Y-m-d\TH:i');

    visit('/opportunities')
        ->assertPresent('[data-test="kanban-card-create-task-'.$opportunity->id.'"]')
        ->click('@kanban-card-create-task-'.$opportunity->id)
        ->assertPresent('[data-test="tasks-quick-create-modal"]')
        ->fill('@tasks-quick-form-title', 'Scheduled from Kanban card')
        ->fill('@tasks-quick-form-due-at', $dueAt)
        ->click('@tasks-quick-form-submit')
        ->assertSee('Task From Kanban');

    expect(Task::where('title', 'Scheduled from Kanban card')->exists())->toBeTrue();
});

it('drags an opportunity card to another stage', function () {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->qualificationProcessing()->create([
        'title' => 'Drag Deal',
        'stage' => PipelineStage::Lead,
    ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->assertPresent('[data-test="kanban-card-'.$opportunity->id.'"]')
        ->drag(
            '@kanban-card-'.$opportunity->id,
            '@kanban-column-qualification',
        )
        ->assertPresent('[data-test="kanban-column-qualification"] [data-test="kanban-card-'.$opportunity->id.'"]');

    $opportunity->refresh();

    expect($opportunity->stage)->toBe(PipelineStage::Qualification);
});
