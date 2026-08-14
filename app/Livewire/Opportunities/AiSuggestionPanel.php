<?php

namespace App\Livewire\Opportunities;

use App\Enums\AgentType;
use App\Enums\QualificationStatus;
use App\Models\Opportunity;
use App\Services\AiOrchestrationService;
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

        $orchestration = app(AiOrchestrationService::class);
        $payload = [
                'trigger' => 'manual_refresh',
                'opportunity_id' => $opportunity->id,
                'client_id' => $opportunity->client_id,
                'user_id' => $userId,
            ];
        $orchestration->dispatch(AgentType::Recommendation, $payload);

        $this->refreshQueued = true;

        Flux::toast(
            variant: 'success',
            text: __('AI insights refresh queued.'),
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
