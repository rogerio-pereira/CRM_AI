<?php

use App\Enums\FollowUpReminderStatus;
use App\Enums\FollowUpSequenceStep;
use App\Enums\PipelineStage;
use App\Mail\FirstContactOutreachMail;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

it('displays the follow-ups page and creates a follow-up', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create(['company_name' => 'Follow Up Browser Co']);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->create(['title' => 'Linked Opp']);

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

    $followUpExists = FollowUp::where('notes', 'Browser follow-up note')
                          ->exists();

    expect($followUpExists)
        ->toBeTrue();
});

it('completes a follow-up from the actions menu', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create(['company_name' => 'Complete Browser Co']);
    $dueAt = Carbon::now()
                    ->addDay();
    $followUp = FollowUp::factory()
                    ->for($client)
                    ->create([
                        'due_at' => $dueAt,
                    ]);

    $this->actingAs($user);

    visit('/follow-ups')
        ->assertSee('Complete Browser Co')
        ->click('@follow-ups-actions-'.$followUp->id)
        ->click('@follow-ups-complete-'.$followUp->id)
        ->uncheck('@follow-ups-hide-completed')
        ->assertPresent('[data-test="follow-ups-row-'.$followUp->id.'"][data-completed-row="true"]')
        ->assertPresent('[data-test="follow-ups-status-badge-'.$followUp->id.'"][data-status="completed"]');

    $freshFollowUp = $followUp->fresh();

    expect($freshFollowUp->reminder_status)
        ->toBe(FollowUpReminderStatus::Completed);
});

it('highlights overdue follow-ups in the table', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create(['company_name' => 'Overdue Browser Co']);
    $followUp = FollowUp::factory()
                    ->for($client)
                    ->overdue()
                    ->create();

    $this->actingAs($user);

    visit('/follow-ups')
        ->assertPresent('[data-test="follow-ups-row-'.$followUp->id.'"][data-overdue-row="true"]');
});

it('sends a sequenced follow-up email from the index', function () {
    Mail::fake();

    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create([
                        'company_name' => 'Send Follow Up Co',
                        'contact_email' => 'pat@sendfollowup.test',
                    ]);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Follow-up Send Deal',
                            'stage' => PipelineStage::ContactSent,
                        ]);
    $followUp = FollowUp::factory()
                    ->for($client)
                    ->sequenceStep(FollowUpSequenceStep::First)
                    ->create([
                        'opportunity_id' => $opportunity->id,
                    ]);

    $this->actingAs($user);

    visit('/follow-ups')
        ->assertPresent('[data-test="follow-ups-send-email-'.$followUp->id.'"]')
        ->click('@follow-ups-send-email-'.$followUp->id)
        ->assertSee('Follow-up email queued.');

    $followUp->refresh();
    $nextFollowUp = FollowUp::where('opportunity_id', $opportunity->id)
                        ->where('id', '!=', $followUp->id)
                        ->first();
    $emailNote = OpportunityNote::where('opportunity_id', $opportunity->id)
                    ->where('body', 'like', '%🔁 A new way to turn local quotes into booked work%')
                    ->first();

    expect($followUp->reminder_status)
        ->toBe(FollowUpReminderStatus::Completed);
    expect($nextFollowUp)
        ->not
        ->toBeNull();
    expect($nextFollowUp->sequence_step)
        ->toBe(FollowUpSequenceStep::Second);
    expect($emailNote)
        ->not
        ->toBeNull();

    Mail::assertSent(FirstContactOutreachMail::class);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-notes"]')
        ->assertSee('🔁 A new way to turn local quotes into booked work');
});
