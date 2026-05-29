<flux:modal wire:model.self="showDetailModal" class="max-w-2xl" data-test="leads-detail-modal">
    @if ($this->detailClient)
        @php($client = $this->detailClient)

        <div class="space-y-6">
            <flux:heading size="lg">{{ $client->company_name }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:subheading>{{ __('Contact') }}</flux:subheading>
                    <flux:text class="text-text-secondary">
                        {{ $client->contact_name ?? '—' }}
                    </flux:text>
                    @if ($client->contact_email)
                        <flux:text class="text-text-muted">{{ $client->contact_email }}</flux:text>
                    @endif
                    @if ($client->contact_phone)
                        <flux:text class="text-text-muted">{{ $client->contact_phone }}</flux:text>
                    @endif
                </div>
                <div>
                    <flux:subheading>{{ __('Status') }}</flux:subheading>
                    <span
                        class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $client->status->badgeClasses() }}"
                        data-test="leads-detail-status-badge"
                        data-status="{{ $client->status->value }}"
                    >
                        {{ $client->status->label() }}
                    </span>
                </div>
                <div>
                    <flux:subheading>{{ __('Website') }}</flux:subheading>
                    <flux:text class="text-text-secondary">{{ $client->website ?? '—' }}</flux:text>
                </div>
                <div>
                    <flux:subheading>{{ __('Lead source') }}</flux:subheading>
                    <flux:text class="text-text-secondary">{{ $client->lead_source ?? '—' }}</flux:text>
                </div>
            </div>

            @if ($client->social_links)
                <div>
                    <flux:subheading>{{ __('Social links') }}</flux:subheading>
                    <ul class="mt-2 space-y-1 text-sm text-text-secondary">
                        @foreach ($client->social_links as $link)
                            <li>{{ $link['platform'] ?? '' }}: {{ $link['url'] ?? '' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($client->qualification_notes)
                <div>
                    <flux:subheading>{{ __('Qualification notes') }}</flux:subheading>
                    <flux:text class="text-text-secondary">{{ $client->qualification_notes }}</flux:text>
                </div>
            @endif

            <div class="rounded-lg border border-border-default bg-app p-4" data-test="leads-detail-ai-insights">
                <flux:subheading>{{ __('AI insights') }}</flux:subheading>
                <flux:text variant="subtle" class="mt-2">
                    {{ __('AI-generated insights will appear here after lead qualification (features 11–12).') }}
                </flux:text>
            </div>

            <div data-test="leads-detail-opportunity-history">
                <flux:subheading>{{ __('Opportunity history') }}</flux:subheading>
                @if ($client->opportunities->isEmpty())
                    <flux:text variant="subtle" class="mt-2">
                        {{ __('No opportunities yet.') }}
                    </flux:text>
                @else
                    <ul class="mt-2 space-y-2 text-sm text-text-secondary">
                        @foreach ($client->opportunities as $opportunity)
                            <li>{{ $opportunity->title }} — {{ $opportunity->stage->label() }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div data-test="leads-detail-follow-up-history">
                <flux:subheading>{{ __('Follow-up history') }}</flux:subheading>
                @if ($client->followUps->isEmpty())
                    <flux:text variant="subtle" class="mt-2">
                        {{ __('No follow-ups yet.') }}
                    </flux:text>
                @else
                    <ul class="mt-2 space-y-2 text-sm text-text-secondary">
                        @foreach ($client->followUps->sortByDesc('due_at') as $followUp)
                            <li>
                                {{ $followUp->due_at->format('M j, Y') }}
                                — {{ $followUp->priority->label() }}
                                — {{ $followUp->reminder_status->label() }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" data-test="leads-detail-close">
                        {{ __('Close') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    @endif
</flux:modal>
