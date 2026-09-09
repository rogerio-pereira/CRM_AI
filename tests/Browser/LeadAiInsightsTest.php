<?php

use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Jobs\RunFirstContactEmailAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\Support\RecommendationFake;

it('opens the lead detail modal with a first-contact email example', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create([
                        'company_name' => 'Lead Email Co',
                    ]);
    $recommendations = RecommendationFake::panelRecommendations();
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Lead Email Deal',
                            'ai_recommendations' => $recommendations,
                        ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->assertPresent('[data-test="leads-detail-opportunity-'.$opportunity->id.'"]')
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSee('A simple way to bring in more local conversations')
        ->assertPresent('[data-test="opportunities-detail-ai-copy-email"]')
        ->assertPresent('[data-test="opportunities-detail-ai-regenerate-email"]')
        ->assertNotPresent('[data-test="opportunities-detail-notes"]');
});

it('hides the first-contact email panel on lead detail when no example exists', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create([
                        'company_name' => 'Lead Hidden Email Co',
                    ]);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Lead Hidden Email Deal',
                        ]);
    $insights = $opportunity->ai_insights;
    $outreachStrategy = $insights['outreach_strategy'];
    unset($outreachStrategy['contact_example']);
    $insights['outreach_strategy'] = $outreachStrategy;
    $opportunity->ai_insights = $insights;
    $opportunity->save();

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-opportunity-'.$opportunity->id.'"]')
        ->assertPresent('[data-test="opportunities-detail-ai-insights"]')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertDontSee('Example first contact email');
});

it('does not show opportunity notes on the lead detail modal', function () {
    $user = User::factory()
                ->create(['name' => 'Lead Notes Author']);
    $client = Client::factory()
                    ->create([
                        'company_name' => 'Lead Notes Co',
                    ]);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->create([
                            'title' => 'Lead Notes Deal',
                        ]);
    OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Internal note that must stay on the opportunity.',
            ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="leads-detail-modal"]')
        ->assertSee('Lead Notes Deal')
        ->assertNotPresent('[data-test="opportunities-detail-notes"]')
        ->assertDontSee('Internal note that must stay on the opportunity.')
        ->assertDontSee('Add a note');
});

it('clears previous insights from lead detail when refreshing AI insights', function () {
    Queue::fake([
        RunQualificationAgentJob::class,
        RunFirstContactEmailAgentJob::class,
    ]);

    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create([
                        'company_name' => 'Lead Refresh Co',
                    ]);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Lead Refresh Deal',
                            'stage' => PipelineStage::Contact,
                        ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->click('@ai-suggestion-refresh')
        ->waitForText('AI insights refresh queued.')
        ->assertDontSee('Ready for a first conversation.')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]');

    $opportunity->refresh();

    expect($opportunity->ai_insights)
        ->toBeNull();
    expect($opportunity->qualification_status)
        ->toBe(QualificationStatus::Qualified);
    expect($opportunity->stage)
        ->toBe(PipelineStage::Contact);

    Queue::assertPushed(RunQualificationAgentJob::class, 1);
    Queue::assertNotPushed(RunFirstContactEmailAgentJob::class);
});

it('regenerates the example email from lead detail without changing stage', function () {
    Queue::fake([
        RunFirstContactEmailAgentJob::class,
        RunQualificationAgentJob::class,
    ]);

    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create([
                        'company_name' => 'Lead Regenerate Co',
                    ]);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Lead Regenerate Deal',
                            'stage' => PipelineStage::Contact,
                        ]);

    $this->actingAs($user);

    visit('/leads')
        ->click('@leads-actions-'.$client->id)
        ->click('@leads-view-'.$client->id)
        ->click('@opportunities-detail-ai-regenerate-email')
        ->waitForText('Example email regeneration queued.')
        ->assertDontSee('A simple way to bring in more local conversations')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSee('Ready for a first conversation.');

    $opportunity->refresh();
    $insights = $opportunity->ai_insights;
    $outreachStrategy = $insights['outreach_strategy'];
    $hasContactExample = array_key_exists('contact_example', $outreachStrategy);

    expect($opportunity->stage)
        ->toBe(PipelineStage::Contact);
    expect($hasContactExample)
        ->toBeFalse();

    Queue::assertPushed(RunFirstContactEmailAgentJob::class, 1);
    Queue::assertNotPushed(RunQualificationAgentJob::class);
});
