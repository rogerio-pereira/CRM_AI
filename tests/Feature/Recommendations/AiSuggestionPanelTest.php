<?php

namespace Tests\Feature\Recommendations;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpReminderStatus;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Events\ContactWithFollowUp;
use App\Jobs\RunFirstContactEmailAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Livewire\Leads\Index as LeadsIndex;
use App\Livewire\Opportunities\AiSuggestionPanel;
use App\Livewire\Opportunities\Index as OpportunitiesIndex;
use App\Mail\FirstContactOutreachMail;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\RecommendationFake;
use Tests\TestCase;

class AiSuggestionPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_detail_renders_structured_recommendation_sections(): void
    {
        $user = User::factory()
                    ->create();
        $recommendations = RecommendationFake::panelRecommendations();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'title' => 'Recommendation Detail Deal',
                                'ai_recommendations' => $recommendations,
                            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="ai-suggestion-panel"')
            ->assertSeeHtml('data-test="opportunities-detail-ai-insights"')
            ->assertSeeHtml('data-test="opportunities-detail-ai-insights-summary"')
            ->assertSee('Ready for a first conversation.')
            ->assertSeeHtml('data-test="opportunities-detail-ai-pain-points"')
            ->assertSee('Outdated website')
            ->assertSeeHtml('data-test="opportunities-detail-ai-fit"')
            ->assertSee('Ready to Contact')
            ->assertSeeHtml('data-test="ai-suggestion-questions"')
            ->assertSee('Where do most new customers hear about you today?')
            ->assertSeeHtml('data-test="ai-suggestion-next-steps"')
            ->assertSeeHtml('data-test="ai-suggestion-create-task-0"')
            ->assertSeeHtml('open-task-for-opportunity')
            ->assertSee('Create Task')
            ->assertSee('Review the example email before any outreach')
            ->assertSee('AI Insight')
            ->assertSee('AI-generated. Not a confirmed human decision.')
            ->assertSeeHtml('data-test="ai-suggestion-refresh"')
            ->assertSeeHtml('data-panel-component-id=')
            ->assertSeeHtml('Livewire.find($event.currentTarget.dataset.panelComponentId).refreshInsights()')
            ->assertSeeHtml('Livewire.find($event.currentTarget.dataset.panelComponentId).regenerateEmail()')
            ->assertSeeHtml('data-parent-component-id=')
            ->assertSeeHtml('.sendEmail()')
            ->assertSeeHtml('closeDetailModal()')
            ->assertSeeHtml('btn-danger')
            ->assertSee('Refresh AI insights')
            ->assertSeeInOrder([
                'opportunities-detail-ai-regenerate-email',
                'opportunities-detail-ai-copy-email',
                'opportunities-detail-ai-send-email',
            ])
            ->assertSeeHtml('btn-primary')
            ->assertSee('Regenerate')
            ->assertSee('Send');
    }

    public function test_lead_detail_renders_related_opportunity_recommendations(): void
    {
        $user = User::factory()
                    ->create();
        $client = Client::factory()
                        ->create([
                            'company_name' => 'Lead Insight Co',
                        ]);
        $recommendations = RecommendationFake::panelRecommendations();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'title' => 'Related Recommendation Deal',
                                'ai_recommendations' => $recommendations,
                            ]);

        $this->actingAs($user);

        $opportunitySelector = 'data-test="leads-detail-opportunity-'.$opportunity->id.'"';

        Livewire::test(LeadsIndex::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml($opportunitySelector)
            ->assertSeeHtml('data-test="ai-suggestion-panel"')
            ->assertSeeHtml('data-test="opportunities-detail-ai-insights"')
            ->assertSee('Ready for a first conversation.')
            ->assertSee('Where do most new customers hear about you today?')
            ->assertSee('Review the example email before any outreach')
            ->assertSeeHtml('data-test="ai-suggestion-create-task-0"')
            ->assertSee('Create Task')
            ->assertSee('AI-generated. Not a confirmed human decision.');
    }

    public function test_refresh_queues_qualification_job(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->withAiRecommendations()
                            ->create([
                                'qualification_notes' => 'Old qualification notes.',
                            ]);
        OpportunityNote::factory()
            ->for($opportunity)
            ->for($user)
            ->create([
                'body' => 'Owner prefers a simple brochure site first.',
            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('refreshInsights')
            ->assertSet('refreshQueued', true)
            ->assertSee('Refresh AI insights')
            ->assertSee('AI insights refresh queued.')
            ->assertDontSee('Ready for a first conversation.');

        $opportunity->refresh();

        $this->assertNull($opportunity->ai_insights);
        $this->assertNull($opportunity->ai_recommendations);
        $this->assertNull($opportunity->qualification_notes);
        $this->assertSame(QualificationStatus::Qualified, $opportunity->qualification_status);
        $this->assertSame(1, $opportunity->notes()->count());

        Queue::assertPushed(RunQualificationAgentJob::class, 1);
        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($opportunity, $user): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;
            $payloadClientId = $job->payload['client_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;
            $payloadUserId = $job->payload['user_id'] ?? null;

            if ($payloadOpportunityId !== $opportunity->id) {
                return false;
            }

            if ($payloadClientId !== $opportunity->client_id) {
                return false;
            }

            if ($trigger !== 'manual_refresh') {
                return false;
            }

            return $payloadUserId === $user->id;
        });
    }

    public function test_refresh_is_rate_limited(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create();

        $this->actingAs($user);

        $component = Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ]);

        $component->call('refreshInsights');
        $component->call('refreshInsights');

        Queue::assertPushed(RunQualificationAgentJob::class, 1);
    }

    public function test_refresh_is_rejected_when_opportunity_is_not_qualified(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationPending()
                            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ])
            ->call('refreshInsights')
            ->assertSet('refreshQueued', false);

        Queue::assertNothingPushed();
    }

    public function test_qualified_opportunity_without_recommendations_shows_qualification_insights(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'ai_recommendations' => null,
                            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->assertSeeHtml('data-test="ai-suggestion-panel"')
            ->assertSeeHtml('data-test="opportunities-detail-ai-insights"')
            ->assertSee('Ready for a first conversation.')
            ->assertDontSeeHtml('data-test="ai-suggestion-empty"')
            ->assertSeeHtml('data-test="ai-suggestion-refresh"');
    }

    public function test_qualified_opportunity_without_insights_or_recommendations_shows_empty_state(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'ai_recommendations' => null,
                                'ai_insights' => null,
                            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->assertSeeHtml('data-test="ai-suggestion-empty"')
            ->assertSee('AI recommendations will appear here after the recommendation job finishes.')
            ->assertSeeHtml('data-test="ai-suggestion-refresh"')
            ->assertSeeHtml('data-panel-component-id=')
            ->assertSeeHtml('Livewire.find($event.currentTarget.dataset.panelComponentId).refreshInsights()')
            ->assertSeeHtml('btn-danger');
    }

    public function test_unqualified_opportunity_does_not_render_the_panel(): void
    {
        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationPending()
                            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ])
            ->assertDontSeeHtml('data-test="ai-suggestion-panel"');
    }

    public function test_regenerate_email_queues_first_contact_email_job(): void
    {
        Queue::fake([
            RunFirstContactEmailAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('regenerateEmail')
            ->assertDontSee('A simple way to bring in more local conversations');

        Queue::assertPushed(RunFirstContactEmailAgentJob::class, 1);
        Queue::assertPushed(RunFirstContactEmailAgentJob::class, function (RunFirstContactEmailAgentJob $job) use ($opportunity, $user): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;
            $payloadClientId = $job->payload['client_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;
            $payloadUserId = $job->payload['user_id'] ?? null;

            if ($payloadOpportunityId !== $opportunity->id) {
                return false;
            }

            if ($payloadClientId !== $opportunity->client_id) {
                return false;
            }

            if ($trigger !== 'manual_email_refresh') {
                return false;
            }

            return $payloadUserId === $user->id;
        });

        $opportunity->refresh();
        $insights = $opportunity->ai_insights;
        $outreachStrategy = $insights['outreach_strategy'];

        $this->assertArrayNotHasKey('contact_example', $outreachStrategy);
    }

    public function test_regenerate_email_clears_recommendation_example_before_queueing(): void
    {
        Queue::fake([
            RunFirstContactEmailAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $recommendations = RecommendationFake::successfulPayload('1', '1')['ai_recommendations'];
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'ai_recommendations' => $recommendations,
                            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('regenerateEmail');

        $opportunity->refresh();
        $insights = $opportunity->ai_insights;
        $storedRecommendations = $opportunity->ai_recommendations;
        $insightOutreach = $insights['outreach_strategy'];
        $recommendationConversation = $storedRecommendations['conversation_strategy'];

        $this->assertArrayNotHasKey('contact_example', $insightOutreach);
        $this->assertArrayNotHasKey('contact_example', $recommendationConversation);
        Queue::assertPushed(RunFirstContactEmailAgentJob::class, 1);
    }

    public function test_regenerate_email_is_rate_limited(): void
    {
        Queue::fake([
            RunFirstContactEmailAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create();

        $this->actingAs($user);

        $component = Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ]);

        $component->call('regenerateEmail');
        $component->call('regenerateEmail');

        Queue::assertPushed(RunFirstContactEmailAgentJob::class, 1);
    }

    public function test_regenerate_email_is_rejected_when_opportunity_is_not_qualified(): void
    {
        Queue::fake([
            RunFirstContactEmailAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationPending()
                            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('regenerateEmail');

        Queue::assertNothingPushed();
    }

    public function test_regenerate_email_is_rejected_when_insights_are_missing(): void
    {
        Queue::fake([
            RunFirstContactEmailAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'ai_insights' => null,
                            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('regenerateEmail');

        Queue::assertNothingPushed();
    }

    public function test_regenerate_email_is_rejected_when_insights_are_empty(): void
    {
        Queue::fake([
            RunFirstContactEmailAgentJob::class,
        ]);

        $user = User::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->qualificationQualified()
                            ->create([
                                'ai_insights' => [],
                            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('regenerateEmail');

        Queue::assertNothingPushed();
    }

    public function test_send_email_sends_the_example_markdown_to_the_lead(): void
    {
        Mail::fake();

        $user = User::factory()
                    ->create();
        $client = Client::factory()
                    ->create([
                        'contact_email' => 'bill@bkturf.test',
                    ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('sendEmail');

        Mail::assertSent(FirstContactOutreachMail::class, function (FirstContactOutreachMail $mail) use ($client): bool {
            $hasRecipient = $mail->hasTo($client->contact_email);
            $hasSubject = $mail->emailSubject === 'A simple way to bring in more local conversations';
            $expectedBody = "Hi there,\n\nI noticed a practical opportunity to turn more local demand into conversations.";
            $hasBody = $mail->markdownBody === $expectedBody;

            if (! $hasRecipient) {
                return false;
            }

            if (! $hasSubject) {
                return false;
            }

            return $hasBody;
        });
    }

    public function test_send_email_moves_opportunity_to_contact_sent_and_creates_follow_up(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-09-09 13:05:00');

        $user = User::factory()
                    ->create();
        $client = Client::factory()
                    ->create([
                        'contact_email' => 'bill@bkturf.test',
                    ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create([
                                'stage' => PipelineStage::Contact,
                            ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('sendEmail')
            ->assertDispatched('opportunity-ai-updated');

        $expectedDueAt = Carbon::parse('2026-09-12 09:00:00');
        $followUp = FollowUp::where('opportunity_id', $opportunity->id)
                        ->first();

        $this->assertDatabaseHas('opportunities', [
                                'id' => $opportunity->id,
                                'stage' => PipelineStage::ContactSent->value,
        ]);
        $this->assertNotNull($followUp);
        $this->assertSame($client->id, $followUp->client_id);
        $this->assertSame(FollowUpPriority::Medium, $followUp->priority);
        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame('Follow up after first-contact email.', $followUp->notes);
        $this->assertTrue($expectedDueAt->equalTo($followUp->due_at));

        $note = OpportunityNote::where('opportunity_id', $opportunity->id)
                    ->first();

        $this->assertNotNull($note);
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame('First Email sent', $note->body);

        Carbon::setTestNow();
    }

    public function test_send_email_dispatches_contact_with_follow_up(): void
    {
        Mail::fake();
        Event::fake([ContactWithFollowUp::class]);

        $user = User::factory()
                    ->create();
        $client = Client::factory()
                    ->create([
                        'contact_email' => 'bill@bkturf.test',
                    ]);
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->qualificationQualified()
                            ->withAiInsights()
                            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
                                'opportunityId' => $opportunity->id,
                            ])
            ->call('sendEmail');

        Event::assertDispatched(
            ContactWithFollowUp::class,
            function (ContactWithFollowUp $event) use ($opportunity, $user): bool {
                $sameOpportunity = $event->opportunity->is($opportunity);
                $sameUser = $event->userId === $user->id;

                if (! $sameOpportunity) {
                    return false;
                }

                return $sameUser;
            },
        );
    }
}
