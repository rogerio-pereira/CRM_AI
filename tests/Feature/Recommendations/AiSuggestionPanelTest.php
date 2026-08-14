<?php

namespace Tests\Feature\Recommendations;

use App\Jobs\RunRecommendationAgentJob;
use App\Livewire\Leads\Index as LeadsIndex;
use App\Livewire\Opportunities\AiSuggestionPanel;
use App\Livewire\Opportunities\Index as OpportunitiesIndex;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AiSuggestionPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_detail_renders_structured_recommendation_sections(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()
            ->qualificationQualified()
            ->withAiRecommendations()
            ->create([
                'title' => 'Recommendation Detail Deal',
            ]);

        $this->actingAs($user);

        Livewire::test(OpportunitiesIndex::class)
            ->call('openDetailModal', $opportunity->id)
            ->assertSeeHtml('data-test="ai-suggestion-panel"')
            ->assertSeeHtml('data-test="ai-suggestion-summary"')
            ->assertSee('Start with a clearer website, then a simple follow-up conversation.')
            ->assertSeeHtml('data-test="ai-suggestion-pain-points"')
            ->assertSee('Outdated website')
            ->assertSeeHtml('data-test="ai-suggestion-opportunities"')
            ->assertSee('Make the first impression easier to act on')
            ->assertSeeHtml('data-test="ai-suggestion-outreach"')
            ->assertSee('Helpful local growth conversation.')
            ->assertSeeHtml('data-test="ai-suggestion-questions"')
            ->assertSee('Where do most new customers hear about you today?')
            ->assertSeeHtml('data-test="ai-suggestion-contact-example"')
            ->assertSee('Helping more visitors feel ready to call')
            ->assertSeeHtml('data-test="ai-suggestion-next-steps"')
            ->assertSee('Review the example email before any outreach')
            ->assertSee('AI Insight')
            ->assertSee('AI-generated. Not a confirmed human decision. This is not sent automatically.')
            ->assertSeeHtml('data-test="ai-suggestion-refresh"')
            ->assertSee('Refresh AI insights');
    }

    public function test_lead_detail_renders_related_opportunity_recommendations(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'company_name' => 'Lead Insight Co',
        ]);
        $opportunity = Opportunity::factory()
            ->for($client)
            ->qualificationQualified()
            ->withAiRecommendations()
            ->create([
                'title' => 'Related Recommendation Deal',
            ]);

        $this->actingAs($user);

        Livewire::test(LeadsIndex::class)
            ->call('openDetailModal', $client->id)
            ->assertSeeHtml('data-test="leads-detail-opportunity-'.$opportunity->id.'"')
            ->assertSeeHtml('data-test="ai-suggestion-panel"')
            ->assertSee('Start with a clearer website, then a simple follow-up conversation.')
            ->assertSee('Make the first impression easier to act on')
            ->assertSee('AI-generated. Not a confirmed human decision. This is not sent automatically.');
    }

    public function test_refresh_queues_recommendation_job(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()
            ->qualificationQualified()
            ->withAiRecommendations()
            ->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ])
            ->call('refreshInsights')
            ->assertSet('refreshQueued', true)
            ->assertSee('Refresh AI insights');

        Queue::assertPushed(RunRecommendationAgentJob::class, 1);
        Queue::assertPushed(RunRecommendationAgentJob::class, function (RunRecommendationAgentJob $job) use ($opportunity, $user): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;
            $payloadClientId = $job->payload['client_id'] ?? null;
            $trigger = $job->payload['trigger'] ?? null;
            $payloadUserId = $job->payload['user_id'] ?? null;

            return $payloadOpportunityId === $opportunity->id
                && $payloadClientId === $opportunity->client_id
                && $trigger === 'manual_refresh'
                && $payloadUserId === $user->id;
        });
    }

    public function test_refresh_is_rate_limited(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->qualificationQualified()->create();

        $this->actingAs($user);

        $component = Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ]);

        $component->call('refreshInsights');
        $component->call('refreshInsights');

        Queue::assertPushed(RunRecommendationAgentJob::class, 1);
    }

    public function test_refresh_is_rejected_when_opportunity_is_not_qualified(): void
    {
        Queue::fake([
            RunRecommendationAgentJob::class,
        ]);

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->qualificationPending()->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ])
            ->call('refreshInsights')
            ->assertSet('refreshQueued', false);

        Queue::assertNothingPushed();
    }

    public function test_qualified_opportunity_without_recommendations_shows_empty_state(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->qualificationQualified()->create([
            'ai_recommendations' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ])
            ->assertSeeHtml('data-test="ai-suggestion-empty"')
            ->assertSee('AI recommendations will appear here after the recommendation job finishes.')
            ->assertSeeHtml('data-test="ai-suggestion-refresh"');
    }

    public function test_unqualified_opportunity_does_not_render_the_panel(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->qualificationPending()->create();

        $this->actingAs($user);

        Livewire::test(AiSuggestionPanel::class, [
            'opportunityId' => $opportunity->id,
        ])
            ->assertDontSeeHtml('data-test="ai-suggestion-panel"');
    }
}
