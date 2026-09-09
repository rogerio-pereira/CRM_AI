<?php

namespace App\Livewire\Opportunities;

use App\Enums\AgentType;
use App\Enums\QualificationStatus;
use App\Models\Opportunity;
use App\Services\AiOrchestrationService;
use App\Services\OpportunityService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class AiSuggestionPanel extends Component
{
    public const REFRESH_RATE_LIMIT_SECONDS = 60;

    public int $opportunityId;

    public bool $refreshQueued = false;

    public function refreshInsights(): void
    {
        $opportunity = Opportunity::findOrFail($this->opportunityId);

        if ($opportunity->qualification_status !== QualificationStatus::Qualified) {
            Flux::toast(
                variant: 'danger',
                text: __('AI insights are available after qualification completes.'),
            );

            return;
        }

        $userId = auth()->id();
        $rateLimitKey = 'ai-recommendations-refresh:'.$userId.':'.$opportunity->id;
        $tooManyAttempts = RateLimiter::tooManyAttempts($rateLimitKey, 1);

        if ($tooManyAttempts) {
            Flux::toast(
                variant: 'warning',
                text: __('Please wait before refreshing AI insights again.'),
            );

            return;
        }

        RateLimiter::hit($rateLimitKey, self::REFRESH_RATE_LIMIT_SECONDS);

        $opportunity->forgetGeneratedAiOutputs();
        $opportunities = app(OpportunityService::class);
        $opportunities->update($opportunity, [
                'ai_insights' => $opportunity->ai_insights,
                'ai_recommendations' => $opportunity->ai_recommendations,
                'qualification_notes' => $opportunity->qualification_notes,
            ]);

        $orchestration = app(AiOrchestrationService::class);
        $payload = [
                'trigger' => 'manual_refresh',
                'opportunity_id' => $opportunity->id,
                'client_id' => $opportunity->client_id,
                'user_id' => $userId,
            ];
        $orchestration->dispatch(AgentType::Qualification, $payload);

        $this->refreshQueued = true;
        $this->dispatch('opportunity-ai-updated');

        Flux::toast(
            variant: 'success',
            text: __('AI insights refresh queued.'),
        );
    }

    public function regenerateEmail(): void
    {
        $opportunity = Opportunity::findOrFail($this->opportunityId);

        $insights = $opportunity->ai_insights;

        if (
            $opportunity->qualification_status !== QualificationStatus::Qualified ||
            ! is_array($insights) ||
            $insights === []
        ) {
            Flux::toast(
                variant: 'danger',
                text: __('AI insights are available after qualification completes.'),
            );

            return;
        }

        $userId = auth()->id();
        $rateLimitKey = 'ai-email-refresh:'.$userId.':'.$opportunity->id;
        $tooManyAttempts = RateLimiter::tooManyAttempts($rateLimitKey, 1);

        if ($tooManyAttempts) {
            Flux::toast(
                variant: 'warning',
                text: __('Please wait before regenerating the email again.'),
            );

            return;
        }

        RateLimiter::hit($rateLimitKey, self::REFRESH_RATE_LIMIT_SECONDS);

        $opportunity->forgetContactExamples();
        $opportunities = app(OpportunityService::class);
        $opportunities->update($opportunity, [
                'ai_insights' => $opportunity->ai_insights,
                'ai_recommendations' => $opportunity->ai_recommendations,
            ]);

        $orchestration = app(AiOrchestrationService::class);
        $payload = [
                'trigger' => 'manual_email_refresh',
                'opportunity_id' => $opportunity->id,
                'client_id' => $opportunity->client_id,
                'user_id' => $userId,
            ];
        $orchestration->dispatch(AgentType::FirstContactEmail, $payload);

        $this->dispatch('opportunity-ai-updated');

        Flux::toast(
            variant: 'success',
            text: __('Example email regeneration queued.'),
        );
    }

    public function render(): View
    {
        $opportunity = Opportunity::find($this->opportunityId);

        return view('livewire.opportunities.ai-suggestion-panel', [
            'opportunity' => $opportunity,
        ]);
    }
}
