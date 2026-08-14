@php
    $insights = [];
    $hasInsights = false;
    $isQualified = false;
    $questionsToAsk = [];
    $nextSteps = [];

    if ($opportunity !== null) {
        $rawInsights = $opportunity->ai_insights;
        $rawRecommendations = $opportunity->ai_recommendations;
        $isQualified = $opportunity->qualification_status === \App\Enums\QualificationStatus::Qualified;

        if (is_array($rawInsights) && $rawInsights !== []) {
            $insights = $rawInsights;
            $hasInsights = true;
        }

        $recommendationOutreach = [];

        if (is_array($rawRecommendations) && $rawRecommendations !== []) {
            $recommendationOutreach = $rawRecommendations['outreach_strategy'] ?? [];
            $nextSteps = $rawRecommendations['next_steps'] ?? [];
        }

        if (! is_array($recommendationOutreach)) {
            $recommendationOutreach = [];
        }

        $questionsToAsk = $recommendationOutreach['questions_to_ask'] ?? [];

        if (! is_array($questionsToAsk)) {
            $questionsToAsk = [];
        }

        if (! is_array($nextSteps)) {
            $nextSteps = [];
        }
    }
@endphp

<div>
@if ($opportunity !== null && ($hasInsights || $isQualified))
    <div data-test="ai-suggestion-panel" data-opportunity-id="{{ $opportunity->id }}">
        @if ($hasInsights)
            @include('livewire.opportunities.partials.ai-insights', [
                'insights' => $insights,
                'questionsToAsk' => $questionsToAsk,
                'nextSteps' => $nextSteps,
                'showRefresh' => $isQualified,
                'opportunityId' => $opportunity->id,
            ])
        @else
            <div class="rounded-lg border border-ai/30 p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <span class="inline-flex rounded-full border border-ai/30 bg-ai/15 px-2 py-0.5 text-xs font-medium text-ai">
                        {{ __('AI Insight') }}
                    </span>

                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="arrow-path"
                        wire:click="refreshInsights"
                        wire:loading.attr="disabled"
                        data-test="ai-suggestion-refresh"
                    >
                        {{ __('Refresh AI insights') }}
                    </flux:button>
                </div>

                @if ($refreshQueued)
                    <flux:text class="mt-3 text-text-secondary" data-test="ai-suggestion-refresh-queued">
                        {{ __('AI insights refresh queued.') }}
                    </flux:text>
                @else
                    <flux:text class="mt-3 text-text-secondary" data-test="ai-suggestion-empty">
                        {{ __('AI recommendations will appear here after the recommendation job finishes.') }}
                    </flux:text>
                @endif
            </div>
        @endif
    </div>
@endif
</div>
