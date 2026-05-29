<?php

use App\Enums\FollowUpReminderStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\User;

it('displays the follow-ups page and creates a follow-up', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Follow Up Browser Co']);
    $opportunity = Opportunity::factory()->for($client)->create(['title' => 'Linked Opp']);

    $this->actingAs($user);

    visit('/follow-ups')
        ->assertNoSmoke()
        ->assertPresent('[data-test="follow-ups-page"]')
        ->click('@follow-ups-create-button')
        ->select('@follow-ups-form-client', (string) $client->id)
        ->select('@follow-ups-form-opportunity', (string) $opportunity->id)
        ->fill('@follow-ups-form-notes', 'Browser follow-up note')
        ->click('@follow-ups-form-submit')
        ->assertSee('Follow Up Browser Co')
        ->assertSee('Linked Opp');

    expect(FollowUp::where('notes', 'Browser follow-up note')->exists())->toBeTrue();
});

it('completes a follow-up from the actions menu', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Complete Browser Co']);
    $followUp = FollowUp::factory()->for($client)->create([
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($user);

    visit('/follow-ups')
        ->assertSee('Complete Browser Co')
        ->click('@follow-ups-actions-'.$followUp->id)
        ->click('@follow-ups-complete-'.$followUp->id)
        ->uncheck('@follow-ups-hide-completed')
        ->assertPresent('[data-test="follow-ups-row-'.$followUp->id.'"][data-completed-row="true"]')
        ->assertPresent('[data-test="follow-ups-status-badge-'.$followUp->id.'"][data-status="completed"]');

    expect($followUp->fresh()->reminder_status)->toBe(FollowUpReminderStatus::Completed);
});

it('highlights overdue follow-ups in the table', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Overdue Browser Co']);
    $followUp = FollowUp::factory()->for($client)->overdue()->create();

    $this->actingAs($user);

    visit('/follow-ups')
        ->assertPresent('[data-test="follow-ups-row-'.$followUp->id.'"][data-overdue-row="true"]');
});
