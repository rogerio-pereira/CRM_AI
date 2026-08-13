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
                    <flux:subheading>{{ __('Notes') }}</flux:subheading>
                    <flux:text class="text-text-secondary">{{ $client->qualification_notes }}</flux:text>
                </div>
            @endif

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
                <div class="flex items-center justify-between gap-2">
                    <flux:subheading>{{ __('Follow-up history') }}</flux:subheading>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="calendar-days"
                        wire:click="openFollowUpModalForClient({{ $client->id }})"
                        data-test="leads-detail-create-follow-up"
                    >
                        {{ __('Add follow-up') }}
                    </flux:button>
                </div>
                @if ($client->followUps->isEmpty())
                    <flux:text variant="subtle" class="mt-2">
                        {{ __('No follow-ups yet.') }}
                    </flux:text>
                @else
                    <ul class="mt-2 space-y-2 text-sm text-text-secondary">
                        @foreach ($client->followUps->sortByDesc('due_at') as $followUp)
                            @php($followUpCompleted = $followUp->reminder_status === \App\Enums\FollowUpReminderStatus::Completed)
                            @php($followUpOverdue = $followUp->isOverdue())
                            <li>
                                <span
                                    @class([
                                        'inline-flex max-w-full flex-wrap items-center gap-2',
                                        'leads-detail-row--completed' => $followUpCompleted,
                                        'leads-detail-row--overdue' => $followUpOverdue,
                                    ])
                                    @if ($followUpCompleted)
                                        data-test="leads-detail-follow-up-completed-{{ $followUp->id }}"
                                    @elseif ($followUpOverdue)
                                        data-test="leads-detail-follow-up-overdue-{{ $followUp->id }}"
                                    @endif
                                >
                                    <span>{{ $followUp->due_at->format('M j, Y') }}</span>
                                    <x-priority-badge :priority="$followUp->priority" />
                                    <x-status-badge
                                        :label="$followUp->reminder_status->label()"
                                        :classes="$followUp->statusBadgeClasses()"
                                        :status="$followUp->reminder_status->value"
                                        data-test="leads-detail-follow-up-status-{{ $followUp->id }}"
                                    />
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div data-test="leads-detail-task-history">
                <div class="flex items-center justify-between gap-2">
                    <flux:subheading>{{ __('Task history') }}</flux:subheading>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="clipboard-document-list"
                        wire:click="openTaskModalForClient({{ $client->id }})"
                        data-test="leads-detail-create-task"
                    >
                        {{ __('Add task') }}
                    </flux:button>
                </div>
                @if ($client->tasks->isEmpty())
                    <flux:text variant="subtle" class="mt-2">
                        {{ __('No tasks yet.') }}
                    </flux:text>
                @else
                    <ul class="mt-2 space-y-2 text-sm text-text-secondary">
                        @foreach ($client->tasks->sortByDesc('due_at') as $task)
                            @php($taskCompleted = $task->status === \App\Enums\TaskStatus::Done)
                            @php($taskOverdue = $task->isOverdue())
                            <li>
                                <span
                                    @class([
                                        'inline-flex max-w-full flex-wrap items-center gap-2',
                                        'leads-detail-row--completed' => $taskCompleted,
                                        'leads-detail-row--overdue' => $taskOverdue,
                                    ])
                                    @if ($taskCompleted)
                                        data-test="leads-detail-task-completed-{{ $task->id }}"
                                    @elseif ($taskOverdue)
                                        data-test="leads-detail-task-overdue-{{ $task->id }}"
                                    @endif
                                >
                                    @if ($task->is_important)
                                        <flux:icon.star
                                            variant="solid"
                                            class="size-4 shrink-0 text-status-warning"
                                            title="{{ __('Important task') }}"
                                            data-test="leads-detail-task-important-{{ $task->id }}"
                                        />
                                    @endif
                                    <span class="font-medium text-text-primary">{{ $task->title }}</span>
                                    <span>{{ $task->due_at->format('M j, Y') }}</span>
                                    <x-priority-badge :priority="$task->priority" />
                                    <x-status-badge
                                        :label="$task->status->label()"
                                        :classes="$task->statusBadgeClasses()"
                                        :status="$task->status->value"
                                        data-test="leads-detail-task-status-{{ $task->id }}"
                                    />
                                </span>
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
