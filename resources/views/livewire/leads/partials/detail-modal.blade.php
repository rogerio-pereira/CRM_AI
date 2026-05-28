<flux:modal wire:model.self="showDetailModal" class="max-w-2xl" data-test="leads-detail-modal">
    @if ($this->viewingClient)
        @php($client = $this->viewingClient)

        <div class="space-y-6">
            <flux:heading size="lg">{{ $client->company_name }}</flux:heading>

            @if ($client->contacts->isNotEmpty())
                <div class="space-y-2">
                    <flux:subheading>{{ __('Contacts') }}</flux:subheading>
                    <ul class="space-y-2">
                        @foreach ($client->contacts as $contact)
                            <li class="rounded-lg border border-border-subtle p-3 text-sm font-light text-text-secondary">
                                <span class="font-medium text-text-primary">{{ $contact->name }}</span>
                                @if ($contact->email)
                                    · {{ $contact->email }}
                                @endif
                                @if ($contact->phone)
                                    · {{ $contact->phone }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text class="text-text-muted">{{ __('Status') }}</flux:text>
                    <flux:text>{{ $client->status->label() }}</flux:text>
                </div>
                <div>
                    <flux:text class="text-text-muted">{{ __('Lead source') }}</flux:text>
                    <flux:text>{{ $client->lead_source ?? '—' }}</flux:text>
                </div>
                <div class="sm:col-span-2">
                    <flux:text class="text-text-muted">{{ __('Website') }}</flux:text>
                    <flux:text>{{ $client->website ?? '—' }}</flux:text>
                </div>
                <div class="sm:col-span-2">
                    <flux:text class="text-text-muted">{{ __('Qualification notes') }}</flux:text>
                    <flux:text class="font-light">{{ $client->qualification_notes ?? '—' }}</flux:text>
                </div>
            </dl>

            <div class="rounded-lg border border-border-subtle bg-app/40 p-4" data-test="client-ai-insights-placeholder">
                <flux:subheading>{{ __('AI insights') }}</flux:subheading>
                @if ($client->aiInsight?->summary)
                    <flux:text class="mt-2 font-light text-text-secondary">{{ $client->aiInsight->summary }}</flux:text>
                @else
                    <flux:text class="mt-2 font-light text-text-secondary">
                        {{ __('AI-generated insights will appear here after qualification (wave 4).') }}
                    </flux:text>
                @endif
            </div>

            <div class="rounded-lg border border-border-subtle p-4" data-test="client-opportunity-history">
                <flux:subheading>{{ __('Opportunity history') }}</flux:subheading>
                <flux:text class="mt-2 font-light text-text-secondary">{{ __('No opportunities yet.') }}</flux:text>
            </div>

            <div class="rounded-lg border border-border-subtle p-4" data-test="client-follow-up-history">
                <flux:subheading>{{ __('Follow-up history') }}</flux:subheading>
                <flux:text class="mt-2 font-light text-text-secondary">{{ __('No follow-ups yet.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeDetailModal">{{ __('Close') }}</flux:button>
                <flux:button type="button" variant="primary" wire:click="openEditModal({{ $client->id }})">{{ __('Edit') }}</flux:button>
            </div>
        </div>
    @endif
</flux:modal>
