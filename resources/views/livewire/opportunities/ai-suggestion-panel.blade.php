@php
    $recommendations = [];
    $hasRecommendations = false;
    $isQualified = false;

    if ($opportunity !== null) {
        $rawRecommendations = $opportunity->ai_recommendations;
        $isQualified = $opportunity->qualification_status === \App\Enums\QualificationStatus::Qualified;

        if (is_array($rawRecommendations) && $rawRecommendations !== []) {
            $recommendations = $rawRecommendations;
            $hasRecommendations = true;
        }
    }

    $summary = (string) ($recommendations['summary'] ?? '');
    $painPoints = $recommendations['pain_points'] ?? [];
    $serviceOpportunities = $recommendations['opportunities'] ?? [];
    $outreach = $recommendations['outreach_strategy'] ?? [];
    $nextSteps = $recommendations['next_steps'] ?? [];

    if (! is_array($painPoints)) {
        $painPoints = [];
    }

    if (! is_array($serviceOpportunities)) {
        $serviceOpportunities = [];
    }

    if (! is_array($outreach)) {
        $outreach = [];
    }

    if (! is_array($nextSteps)) {
        $nextSteps = [];
    }

    $positioning = (string) ($outreach['positioning'] ?? '');
    $talkingPoints = $outreach['talking_points'] ?? [];
    $avoidPoints = $outreach['avoid'] ?? [];
    $questionsToAsk = $outreach['questions_to_ask'] ?? [];
    $contactExample = $outreach['contact_example'] ?? [];

    if (! is_array($talkingPoints)) {
        $talkingPoints = [];
    }

    if (! is_array($avoidPoints)) {
        $avoidPoints = [];
    }

    if (! is_array($questionsToAsk)) {
        $questionsToAsk = [];
    }

    if (! is_array($contactExample)) {
        $contactExample = [];
    }

    $contactSubject = (string) ($contactExample['subject'] ?? '');
    $contactBody = (string) ($contactExample['body'] ?? '');
    $hasContactExample = filled($contactSubject) || filled($contactBody);
    $copyText = 'Subject: '.$contactSubject."\n\n".$contactBody;
    $copyText = trim($copyText);
@endphp

<div>
@if ($opportunity !== null && ($hasRecommendations || $isQualified))
    <div
        class="rounded-lg border border-ai/30 p-4"
        data-test="ai-suggestion-panel"
        data-opportunity-id="{{ $opportunity->id }}"
    >
        <div class="flex flex-wrap items-start justify-between gap-2">
            <span class="inline-flex rounded-full border border-ai/30 bg-ai/15 px-2 py-0.5 text-xs font-medium text-ai">
                {{ __('AI Insight') }}
            </span>

            @if ($isQualified)
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
            @endif
        </div>

        @if ($refreshQueued && ! $hasRecommendations)
            <flux:text class="mt-3 text-text-secondary" data-test="ai-suggestion-refresh-queued">
                {{ __('AI insights refresh queued.') }}
            </flux:text>
        @elseif (! $hasRecommendations)
            <flux:text class="mt-3 text-text-secondary" data-test="ai-suggestion-empty">
                {{ __('AI recommendations will appear here after the recommendation job finishes.') }}
            </flux:text>
        @endif

        @if (filled($summary))
            <flux:text class="mt-3 text-text-secondary" data-test="ai-suggestion-summary">
                {{ $summary }}
            </flux:text>
        @endif

        @if ($painPoints !== [])
            <div class="mt-4" data-test="ai-suggestion-pain-points">
                <flux:subheading>{{ __('Pain points') }}</flux:subheading>
                <ul class="mt-2 space-y-3">
                    @foreach ($painPoints as $painPoint)
                        @if (is_array($painPoint))
                            @php
                                $painTitle = (string) ($painPoint['title'] ?? '');
                                $painEvidence = (string) ($painPoint['evidence'] ?? '');
                                $painImpact = (string) ($painPoint['business_impact'] ?? '');
                            @endphp
                            <li class="rounded-md border border-border-default p-3">
                                @if (filled($painTitle))
                                    <div class="font-medium text-text-primary">{{ $painTitle }}</div>
                                @endif
                                @if (filled($painEvidence))
                                    <flux:text class="mt-1 text-text-secondary">{{ $painEvidence }}</flux:text>
                                @endif
                                @if (filled($painImpact))
                                    <flux:text class="mt-1 text-text-muted">{{ $painImpact }}</flux:text>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($serviceOpportunities !== [])
            <div class="mt-4" data-test="ai-suggestion-opportunities">
                <flux:subheading>{{ __('Opportunity analysis') }}</flux:subheading>
                <ul class="mt-2 space-y-3">
                    @foreach ($serviceOpportunities as $serviceOpportunity)
                        @if (is_array($serviceOpportunity))
                            @php
                                $serviceTitle = (string) ($serviceOpportunity['title'] ?? '');
                                $whyItMatters = (string) ($serviceOpportunity['why_it_matters'] ?? '');
                                $priorityValue = (string) ($serviceOpportunity['priority'] ?? '');
                                $priority = \App\Enums\TaskPriority::tryFrom($priorityValue);
                            @endphp
                            <li class="rounded-md border border-border-default p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if (filled($serviceTitle))
                                        <div class="font-medium text-text-primary">{{ $serviceTitle }}</div>
                                    @endif
                                    @if ($priority !== null)
                                        <x-priority-badge :priority="$priority" />
                                    @endif
                                </div>
                                @if (filled($whyItMatters))
                                    <flux:text class="mt-1 text-text-secondary">{{ $whyItMatters }}</flux:text>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        @if (filled($positioning) || $talkingPoints !== [] || $avoidPoints !== [] || $questionsToAsk !== [])
            <div class="mt-4" data-test="ai-suggestion-outreach">
                <flux:subheading>{{ __('Outreach strategy') }}</flux:subheading>
                @if (filled($positioning))
                    <flux:text class="mt-2 text-text-secondary">{{ $positioning }}</flux:text>
                @endif
                @if ($talkingPoints !== [])
                    <div class="mt-3">
                        <flux:subheading>{{ __('Talking points') }}</flux:subheading>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-text-secondary">
                            @foreach ($talkingPoints as $talkingPoint)
                                @if (is_string($talkingPoint) && filled($talkingPoint))
                                    <li>{{ $talkingPoint }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($questionsToAsk !== [])
                    <div class="mt-3" data-test="ai-suggestion-questions">
                        <flux:subheading>{{ __('Questions to ask') }}</flux:subheading>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-text-secondary">
                            @foreach ($questionsToAsk as $question)
                                @if (is_string($question) && filled($question))
                                    <li>{{ $question }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($avoidPoints !== [])
                    <div class="mt-3">
                        <flux:subheading>{{ __('Avoid') }}</flux:subheading>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-text-muted">
                            @foreach ($avoidPoints as $avoidPoint)
                                @if (is_string($avoidPoint) && filled($avoidPoint))
                                    <li>{{ $avoidPoint }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @if ($hasContactExample)
            <div
                class="mt-4 rounded-md border border-border-default p-3"
                data-test="ai-suggestion-contact-example"
                x-data="{
                    copied: false,
                    async copy() {
                        try {
                            await navigator.clipboard.writeText(this.$refs.recommendationEmail.textContent);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 1500);
                        } catch (e) {
                            console.warn('Could not copy to clipboard');
                        }
                    }
                }"
            >
                <div class="flex items-start justify-between gap-2">
                    <flux:subheading>{{ __('Example first contact email') }}</flux:subheading>
                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="document-duplicate"
                        x-on:click="copy()"
                        data-test="ai-suggestion-copy-email"
                    >
                        <span x-show="!copied">{{ __('Copy') }}</span>
                        <span x-cloak x-show="copied">{{ __('Copied') }}</span>
                    </flux:button>
                </div>
                <pre class="hidden" x-ref="recommendationEmail">{{ $copyText }}</pre>
                @if (filled($contactSubject))
                    <div class="mt-2 text-sm text-text-primary" data-test="ai-suggestion-contact-subject">
                        <span class="text-text-muted">{{ __('Subject') }}:</span>
                        {{ $contactSubject }}
                    </div>
                @endif
                @if (filled($contactBody))
                    <p
                        class="mt-2 whitespace-pre-wrap text-sm text-text-secondary"
                        data-test="ai-suggestion-contact-body"
                    >
                        {{ $contactBody }}
                    </p>
                @endif
            </div>
        @endif

        @if ($nextSteps !== [])
            <div class="mt-4" data-test="ai-suggestion-next-steps">
                <flux:subheading>{{ __('Next-step recommendations') }}</flux:subheading>
                <ul class="mt-2 space-y-3">
                    @foreach ($nextSteps as $nextStep)
                        @if (is_array($nextStep))
                            @php
                                $stepTitle = (string) ($nextStep['title'] ?? '');
                                $stepReason = (string) ($nextStep['reason'] ?? '');
                            @endphp
                            <li class="rounded-md border border-border-default p-3">
                                @if (filled($stepTitle))
                                    <div class="font-medium text-text-primary">{{ $stepTitle }}</div>
                                @endif
                                @if (filled($stepReason))
                                    <flux:text class="mt-1 text-text-secondary">{{ $stepReason }}</flux:text>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        <flux:text variant="subtle" class="mt-4" data-test="ai-suggestion-disclaimer">
            {{ __('AI-generated. Not a confirmed human decision. This is not sent automatically.') }}
        </flux:text>
    </div>
@endif
</div>
