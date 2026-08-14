<flux:modal wire:model.self="showDetailModal" class="max-w-2xl" data-test="opportunities-detail-modal">
    @if ($this->detailOpportunity)
        @php($opportunity = $this->detailOpportunity)
        @php($insights = $opportunity->ai_insights)
        @php($hasInsights = is_array($insights) && $insights !== [])
        @php($websiteUrl = \App\Support\UrlNormalizer::normalize($opportunity->client->website))

        <div class="space-y-6">
            <flux:heading size="lg">{{ $opportunity->title }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:subheading>{{ __('Estimated value') }}</flux:subheading>
                    <flux:text class="text-text-secondary">
                        @if ($opportunity->estimated_value !== null)
                            {{ number_format((float) $opportunity->estimated_value, 2) }}
                        @else
                            {{ __('—') }}
                        @endif
                    </flux:text>
                </div>
                <div>
                    <flux:subheading>{{ __('Stage') }}</flux:subheading>
                    <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $opportunity->stage->badgeClasses() }}">
                        {{ $opportunity->stage->label() }}
                    </span>
                </div>
                <div>
                    <flux:subheading>{{ __('Status') }}</flux:subheading>
                    <flux:tooltip :content="$opportunity->status->description()" position="right">
                        <span
                            class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium bg-status-neutral/20 text-status-neutral border-status-neutral/50"
                            data-test="opportunities-detail-status-badge"
                        >
                            {{ $opportunity->status->label() }}
                        </span>
                    </flux:tooltip>
                </div>
                <div>
                    <flux:subheading>{{ __('Qualification') }}</flux:subheading>
                    <x-status-badge
                        :label="$opportunity->qualification_status->label()"
                        :classes="$opportunity->qualification_status->badgeClasses()"
                        :status="$opportunity->qualification_status->value"
                        data-test="opportunities-detail-qualification-badge"
                    />
                    @if ($opportunity->qualification_status === \App\Enums\QualificationStatus::Failed && $opportunity->qualification_last_error)
                        <flux:text class="mt-2 text-status-danger" data-test="opportunities-detail-qualification-error">
                            {{ $opportunity->qualification_last_error }}
                        </flux:text>
                    @endif
                </div>
                <div>
                    <flux:subheading>{{ __('Contact') }}</flux:subheading>
                    <flux:text class="text-text-secondary" data-test="opportunities-detail-contact-name">
                        {{ $opportunity->client->contact_name ?? __('—') }}
                    </flux:text>
                    @if ($opportunity->client->contact_email)
                        <flux:text class="text-text-muted" data-test="opportunities-detail-contact-email">
                            {{ $opportunity->client->contact_email }}
                        </flux:text>
                    @endif
                    @if ($opportunity->client->contact_phone)
                        <flux:text class="text-text-muted" data-test="opportunities-detail-contact-phone">
                            {{ $opportunity->client->contact_phone }}
                        </flux:text>
                    @endif
                </div>
                <div>
                    <flux:subheading>{{ __('Company') }}</flux:subheading>
                    <flux:text class="text-text-secondary" data-test="opportunities-detail-company-name">
                        {{ $opportunity->client->company_name }}
                    </flux:text>
                </div>
                <div class="sm:col-span-2">
                    <flux:subheading>{{ __('Website') }}</flux:subheading>
                    @if ($websiteUrl !== null)
                        <a
                            href="{{ $websiteUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-primary hover:text-primary-hover"
                            data-test="opportunities-detail-website-link"
                        >
                            {{ $opportunity->client->website }}
                        </a>
                    @else
                        <flux:text class="text-text-secondary" data-test="opportunities-detail-website">{{ __('—') }}</flux:text>
                    @endif
                </div>
            </div>

            @if ($opportunity->qualification_notes)
                <div>
                    <flux:subheading>{{ __('Notes') }}</flux:subheading>
                    <flux:text class="text-text-secondary" data-test="opportunities-detail-qualification-notes">
                        {{ $opportunity->qualification_notes }}
                    </flux:text>
                </div>
            @endif

            @if ($hasInsights)
                @include('livewire.opportunities.partials.ai-insights', ['insights' => $insights])
            @endif

            <div>
                <a
                    href="{{ route('leads.index') }}"
                    wire:navigate
                    class="text-sm text-primary hover:text-primary-hover"
                    data-test="opportunities-detail-client-link"
                >
                    {{ __('View client in Leads') }}
                </a>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button
                    type="button"
                    variant="ghost"
                    wire:click="openEditModal({{ $opportunity->id }})"
                    data-test="opportunities-detail-edit"
                >
                    {{ __('Edit') }}
                </flux:button>

                <flux:modal.close>
                    <flux:button type="button" variant="ghost" data-test="opportunities-detail-close">
                        {{ __('Close') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    @endif
</flux:modal>
