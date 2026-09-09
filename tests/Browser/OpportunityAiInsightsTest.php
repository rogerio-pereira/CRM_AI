<?php

use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Jobs\RunFirstContactEmailAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\Support\RecommendationFake;

/**
 * @param  array<string, mixed>  $insights
 * @param  array<string, mixed>|null  $contactExample
 * @return array<string, mixed>
 */
function insightsWithContactExample(array $insights, ?array $contactExample): array
{
    $outreachStrategy = $insights['outreach_strategy'] ?? [];

    if ($contactExample === null) {
        unset($outreachStrategy['contact_example']);
    } else {
        $outreachStrategy['contact_example'] = $contactExample;
    }

    $insights['outreach_strategy'] = $outreachStrategy;

    return $insights;
}

it('hides the first-contact email panel when no example exists', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Insights Without Email Deal',
                        ]);
    $insights = $opportunity->ai_insights;
    $insightsWithoutExample = insightsWithContactExample($insights, null);
    $opportunity->ai_insights = $insightsWithoutExample;
    $opportunity->save();

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-insights"]')
        ->assertSee('Ready for a first conversation.')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertNotPresent('[data-test="opportunities-detail-ai-copy-email"]')
        ->assertNotPresent('[data-test="opportunities-detail-ai-regenerate-email"]')
        ->assertDontSee('Example first contact email');
});

it('hides the first-contact email panel when the example is empty', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Empty Email Example Deal',
                        ]);
    $insights = $opportunity->ai_insights;
    $emptyExample = [
        'channel' => 'email',
        'subject' => '',
        'body' => '',
    ];
    $insightsWithEmptyExample = insightsWithContactExample($insights, $emptyExample);
    $opportunity->ai_insights = $insightsWithEmptyExample;
    $opportunity->save();

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-insights"]')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]');
});

it('shows the first-contact email panel when only a subject exists', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Subject Only Email Deal',
                        ]);
    $insights = $opportunity->ai_insights;
    $subjectOnlyExample = [
        'channel' => 'email',
        'subject' => 'Only a subject line',
        'body' => '',
    ];
    $insightsWithSubject = insightsWithContactExample($insights, $subjectOnlyExample);
    $opportunity->ai_insights = $insightsWithSubject;
    $opportunity->save();

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSeeIn('@opportunities-detail-ai-contact-subject', 'Only a subject line')
        ->assertPresent('[data-test="opportunities-detail-ai-copy-email"]')
        ->assertPresent('[data-test="opportunities-detail-ai-regenerate-email"]');
});

it('shows the first-contact email panel when only a body exists', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Body Only Email Deal',
                        ]);
    $insights = $opportunity->ai_insights;
    $bodyOnlyExample = [
        'channel' => 'email',
        'subject' => '',
        'body' => 'Just a body for the first contact email.',
    ];
    $insightsWithBody = insightsWithContactExample($insights, $bodyOnlyExample);
    $opportunity->ai_insights = $insightsWithBody;
    $opportunity->save();

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSeeIn('@opportunities-detail-ai-contact-body', 'Just a body for the first contact email.')
        ->assertPresent('[data-test="opportunities-detail-ai-copy-email"]')
        ->assertPresent('[data-test="opportunities-detail-ai-regenerate-email"]');
});

it('copies the example email without raising JavaScript errors', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Copy Email Deal',
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-copy-email"]')
        ->click('@opportunities-detail-ai-copy-email')
        ->assertNoJavaScriptErrors()
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]');
});

it('does not render the AI panel for an unqualified opportunity', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationPending()
                        ->create([
                            'title' => 'Unqualified Panel Deal',
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-notes"]')
        ->assertNotPresent('[data-test="ai-suggestion-panel"]')
        ->assertNotPresent('[data-test="ai-suggestion-refresh"]')
        ->assertNotPresent('[data-test="opportunities-detail-ai-regenerate-email"]');
});

it('shows an empty AI panel with refresh when a qualified opportunity has no insights', function () {
    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->create([
                            'title' => 'Empty Insights Deal',
                            'ai_insights' => null,
                            'ai_recommendations' => null,
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="ai-suggestion-panel"]')
        ->assertPresent('[data-test="ai-suggestion-empty"]')
        ->assertSee('AI recommendations will appear here after the recommendation job finishes.')
        ->assertPresent('[data-test="ai-suggestion-refresh"]')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]');
});

it('clears previous insights and email when refreshing AI insights', function () {
    Queue::fake([
        RunQualificationAgentJob::class,
        RunFirstContactEmailAgentJob::class,
    ]);

    $user = User::factory()
                ->create(['name' => 'Refresh Notes Author']);
    $recommendations = RecommendationFake::panelRecommendations();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Refresh Insights Deal',
                            'stage' => PipelineStage::Contact,
                            'qualification_notes' => 'Old qualification notes for this deal.',
                            'ai_recommendations' => $recommendations,
                        ]);
    OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner prefers a simple brochure site first.',
            ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSee('Ready for a first conversation.')
        ->assertSee('Old qualification notes for this deal.')
        ->click('@ai-suggestion-refresh')
        ->waitForText('AI insights refresh queued.')
        ->assertPresent('[data-test="ai-suggestion-refresh-queued"]')
        ->assertDontSee('Ready for a first conversation.')
        ->assertDontSee('A simple way to bring in more local conversations')
        ->assertDontSee('Old qualification notes for this deal.')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSee('Owner prefers a simple brochure site first.')
        ->assertPresent('[data-test="ai-suggestion-refresh"]');

    $opportunity->refresh();

    expect($opportunity->ai_insights)
        ->toBeNull();
    expect($opportunity->ai_recommendations)
        ->toBeNull();
    expect($opportunity->qualification_notes)
        ->toBeNull();
    expect($opportunity->qualification_status)
        ->toBe(QualificationStatus::Qualified);
    expect($opportunity->stage)
        ->toBe(PipelineStage::Contact);
    $noteCount = $opportunity->notes()
                    ->count();

    expect($noteCount)
        ->toBe(1);

    Queue::assertPushed(RunQualificationAgentJob::class, 1);
    Queue::assertNotPushed(RunFirstContactEmailAgentJob::class);
});

it('rate limits a second AI insights refresh from the empty panel', function () {
    Queue::fake([
        RunQualificationAgentJob::class,
    ]);

    $user = User::factory()
                ->create();
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Refresh Rate Limit Deal',
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->click('@ai-suggestion-refresh')
        ->waitForText('AI insights refresh queued.')
        ->click('@ai-suggestion-refresh')
        ->waitForText('Please wait before refreshing AI insights again.');

    Queue::assertPushed(RunQualificationAgentJob::class, 1);
});

it('clears the example email on regenerate without changing insights, notes, or stage', function () {
    Queue::fake([
        RunFirstContactEmailAgentJob::class,
        RunQualificationAgentJob::class,
    ]);

    $user = User::factory()
                ->create(['name' => 'Regenerate Notes Author']);
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Regenerate Keeps Insights Deal',
                            'stage' => PipelineStage::Contact,
                        ]);
    OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Keep this note after regenerating the email.',
            ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->assertPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->click('@opportunities-detail-ai-regenerate-email')
        ->waitForText('Example email regeneration queued.')
        ->assertDontSee('A simple way to bring in more local conversations')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]')
        ->assertSee('Ready for a first conversation.')
        ->assertSee('Outdated website')
        ->assertSee('Keep this note after regenerating the email.');

    $opportunity->refresh();
    $insights = $opportunity->ai_insights;
    $outreachStrategy = $insights['outreach_strategy'];
    $noteCount = $opportunity->notes()
                    ->count();
    $hasInsightContactExample = array_key_exists('contact_example', $outreachStrategy);

    expect($opportunity->stage)
        ->toBe(PipelineStage::Contact);
    expect($opportunity->qualification_status)
        ->toBe(QualificationStatus::Qualified);
    expect($noteCount)
        ->toBe(1);
    expect($hasInsightContactExample)
        ->toBeFalse();

    Queue::assertPushed(RunFirstContactEmailAgentJob::class, 1);
    Queue::assertNotPushed(RunQualificationAgentJob::class);
});

it('clears recommendation email drafts when regenerating from the detail modal', function () {
    Queue::fake([
        RunFirstContactEmailAgentJob::class,
    ]);

    $user = User::factory()
                ->create();
    $recommendationPayload = RecommendationFake::successfulPayload('1', '1');
    $recommendations = $recommendationPayload['ai_recommendations'];
    $opportunity = Opportunity::factory()
                        ->qualificationQualified()
                        ->withAiInsights()
                        ->create([
                            'title' => 'Regenerate Recommendation Email Deal',
                            'ai_recommendations' => $recommendations,
                        ]);

    $this->actingAs($user);

    visit('/opportunities')
        ->click('@kanban-card-open-'.$opportunity->id)
        ->click('@opportunities-detail-ai-regenerate-email')
        ->waitForText('Example email regeneration queued.')
        ->assertNotPresent('[data-test="opportunities-detail-ai-contact-example"]');

    $opportunity->refresh();
    $insights = $opportunity->ai_insights;
    $storedRecommendations = $opportunity->ai_recommendations;
    $insightOutreach = $insights['outreach_strategy'];
    $recommendationConversation = $storedRecommendations['conversation_strategy'];

    $insightHasContactExample = array_key_exists('contact_example', $insightOutreach);
    $recommendationHasContactExample = array_key_exists('contact_example', $recommendationConversation);

    expect($insightHasContactExample)
        ->toBeFalse();
    expect($recommendationHasContactExample)
        ->toBeFalse();
});
