<flux:modal wire:model.self="showDetailModal" class="max-w-2xl" data-test="opportunities-detail-modal">
    @if ($this->detailOpportunity)
        @php($opportunity = $this->detailOpportunity)

        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <flux:heading size="lg">{{ $opportunity->title }}</flux:heading>
                <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $opportunity->stage->badgeClasses() }}">
                    {{ $opportunity->stage->label() }}
                </span>
            </div>

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
                    <flux:subheading>{{ __('Status') }}</flux:subheading>
                    <flux:badge>{{ $opportunity->status->label() }}</flux:badge>
                </div>
            </div>

            <div class="rounded-lg border border-border-default bg-app p-4">
                <flux:subheading>{{ __('Client summary') }}</flux:subheading>
                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <div class="font-medium text-text-primary">{{ $opportunity->client->company_name }}</div>
                    @if ($opportunity->client->contact_name)
                        <div>{{ $opportunity->client->contact_name }}</div>
                    @endif
                    @if ($opportunity->client->contact_email)
                        <div>{{ $opportunity->client->contact_email }}</div>
                    @endif
                </div>

                <div class="mt-4">
                    <flux:subheading>{{ __('Qualification') }}</flux:subheading>
                    <x-status-badge
                        :label="$opportunity->client->qualification_status->label()"
                        :classes="$opportunity->client->qualification_status->badgeClasses()"
                        :status="$opportunity->client->qualification_status->value"
                        data-test="opportunities-detail-qualification-badge"
                    />
                    @if ($opportunity->client->qualification_status === \App\Enums\QualificationStatus::Failed && $opportunity->client->qualification_last_error)
                        <flux:text class="mt-2 text-status-danger" data-test="opportunities-detail-qualification-error">
                            {{ $opportunity->client->qualification_last_error }}
                        </flux:text>
                    @endif
                </div>

                <div class="mt-4">
                    <a
                        href="{{ route('leads.index') }}"
                        wire:navigate
                        class="text-sm text-primary hover:text-primary-hover"
                        data-test="opportunities-detail-client-link"
                    >
                        {{ __('View client in Leads') }}
                    </a>
                </div>
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
