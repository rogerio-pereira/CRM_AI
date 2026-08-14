@php
    $summary = (string) ($insights['summary'] ?? '');
    $fit = $insights['fit'] ?? [];
    $painPoints = $insights['pain_points'] ?? [];
    $serviceOpportunities = $insights['opportunities'] ?? [];
    $outreach = $insights['outreach_strategy'] ?? [];

    if (! is_array($fit)) {
        $fit = [];
    }

    if (! is_array($painPoints)) {
        $painPoints = [];
    }

    if (! is_array($serviceOpportunities)) {
        $serviceOpportunities = [];
    }

    if (! is_array($outreach)) {
        $outreach = [];
    }

    $fitLevel = (string) ($fit['level'] ?? '');
    $fitLabel = (string) ($fit['label'] ?? '');
    $fitReason = (string) ($fit['reason'] ?? '');
    $positioning = (string) ($outreach['positioning'] ?? '');
    $talkingPoints = $outreach['talking_points'] ?? [];
    $avoidPoints = $outreach['avoid'] ?? [];
    $contactExample = $outreach['contact_example'] ?? [];

    if (! is_array($talkingPoints)) {
        $talkingPoints = [];
    }

    if (! is_array($avoidPoints)) {
        $avoidPoints = [];
    }

    if (! is_array($contactExample)) {
        $contactExample = [];
    }

    $contactSubject = (string) ($contactExample['subject'] ?? '');
    $contactBody = (string) ($contactExample['body'] ?? '');
    $hasContactExample = filled($contactSubject) || filled($contactBody);
    $copyText = 'Subject: '.$contactSubject."\n\n".$contactBody;
    $copyText = trim($copyText);

    $questionsToAsk = $questionsToAsk ?? [];
    $nextSteps = $nextSteps ?? [];
    $showRefresh = $showRefresh ?? false;

    if (! is_array($questionsToAsk)) {
        $questionsToAsk = [];
    }

    if (! is_array($nextSteps)) {
        $nextSteps = [];
    }

    if ($fitLevel === 'high') {
        $fitClasses = 'bg-status-success/20 text-status-success border-status-success/50';
    } elseif ($fitLevel === 'medium') {
        $fitClasses = 'bg-status-warning/20 text-status-warning border-status-warning/50';
    } else {
        $fitClasses = 'bg-status-neutral/20 text-status-neutral border-status-neutral/50';
    }
@endphp

<div class="rounded-lg border border-ai/30 p-4" data-test="opportunities-detail-ai-insights">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <span class="inline-flex rounded-full border border-ai/30 bg-ai/15 px-2 py-0.5 text-xs font-medium text-ai">
            {{ __('AI Insight') }}
        </span>

        @if ($showRefresh)
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

    @if (filled($summary))
        <flux:text class="mt-3 text-text-secondary" data-test="opportunities-detail-ai-insights-summary">
            {{ $summary }}
        </flux:text>
    @endif

    @if (filled($fitLabel) || filled($fitReason))
        <div class="mt-4" data-test="opportunities-detail-ai-fit">
            <flux:subheading>{{ __('Fit') }}</flux:subheading>
            @if (filled($fitLabel))
                <x-status-badge
                    :label="$fitLabel"
                    :classes="$fitClasses"
                    :status="$fitLevel"
                    class="mt-2"
                    data-test="opportunities-detail-ai-fit-badge"
                />
            @endif
            @if (filled($fitReason))
                <flux:text class="mt-2 text-text-secondary">{{ $fitReason }}</flux:text>
            @endif
        </div>
    @endif

    @if ($painPoints !== [])
        <div class="mt-4" data-test="opportunities-detail-ai-pain-points">
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
        <div class="mt-4" data-test="opportunities-detail-ai-opportunities">
            <flux:subheading>{{ __('Service opportunities') }}</flux:subheading>
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
        <div class="mt-4" data-test="opportunities-detail-ai-outreach">
            <flux:subheading>{{ __('How to talk with the client') }}</flux:subheading>
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
            data-test="opportunities-detail-ai-contact-example"
            x-data="{
                copied: false,
                async copy() {
                    try {
                        await navigator.clipboard.writeText(this.$refs.contactEmail.textContent);
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
                    data-test="opportunities-detail-ai-copy-email"
                >
                    <span x-show="!copied">{{ __('Copy') }}</span>
                    <span x-cloak x-show="copied">{{ __('Copied') }}</span>
                </flux:button>
            </div>
            <pre class="hidden" x-ref="contactEmail">{{ $copyText }}</pre>
            @if (filled($contactSubject))
                <div class="mt-2 text-sm text-text-primary" data-test="opportunities-detail-ai-contact-subject">
                    <span class="text-text-muted">{{ __('Subject') }}:</span>
                    {{ $contactSubject }}
                </div>
            @endif
            @if (filled($contactBody))
                <p
                    class="mt-2 whitespace-pre-wrap text-sm text-text-secondary"
                    data-test="opportunities-detail-ai-contact-body"
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
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    @if (filled($stepTitle))
                                        <div class="font-medium text-text-primary">{{ $stepTitle }}</div>
                                    @endif
                                    @if (filled($stepReason))
                                        <flux:text class="mt-1 text-text-secondary">{{ $stepReason }}</flux:text>
                                    @endif
                                </div>
                                <flux:button
                                    type="button"
                                    size="xs"
                                    variant="ghost"
                                    icon="clipboard-document-list"
                                    data-step-title="{{ $stepTitle }}"
                                    data-step-reason="{{ $stepReason }}"
                                    x-on:click="Livewire.dispatch('open-task-for-opportunity', { opportunityId: {{ (int) $opportunityId }}, title: $el.dataset.stepTitle, description: $el.dataset.stepReason })"
                                    data-test="ai-suggestion-create-task-{{ $loop->index }}"
                                >
                                    {{ __('Create Task') }}
                                </flux:button>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif

    <flux:text variant="subtle" class="mt-4">
        {{ __('AI-generated. Not a confirmed human decision.') }}
    </flux:text>
</div>
