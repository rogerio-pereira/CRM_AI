<?php

use App\Concerns\ClientValidationRules;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Services\ClientService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Leads / Clients')] class extends Component {
    use AuthorizesRequests, ClientValidationRules, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingClientId = null;

    public ?int $viewingClientId = null;

    public ?int $deletingClientId = null;

    public string $company_name = '';

    public string $website = '';

    public string $lead_source = '';

    public string $qualification_notes = '';

    /** @var array<int, array{name: string, email: string, phone: string}> */
    public array $contacts = [];

    /** @var array{linkedin: string, twitter: string, facebook: string} */
    public array $social_links = [
        'linkedin' => '',
        'twitter' => '',
        'facebook' => '',
    ];

    public function mount(): void
    {
        $this->resetContactRows();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function clients()
    {
        $status = null;

        if ($this->statusFilter !== '') {
            $status = ClientStatus::from($this->statusFilter);
        }

        $search = null;

        if ($this->search !== '') {
            $search = $this->search;
        }

        return app(ClientService::class)->list(
            search: $search,
            status: $status,
        );
    }

    #[Computed]
    public function viewingClient(): ?Client
    {
        if ($this->viewingClientId === null) {
            return null;
        }

        return Client::query()->find($this->viewingClientId);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Client::class);
        $this->resetForm();
        $this->editingClientId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->authorize('update', $client);

        $this->fillFormFromClient($client);
        $this->editingClientId = $client->id;
        $this->showFormModal = true;
    }

    public function openDetailModal(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->authorize('view', $client);

        $this->viewingClientId = $client->id;
        $this->showDetailModal = true;
    }

    public function openDeleteModal(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->authorize('delete', $client);

        $this->deletingClientId = $client->id;
        $this->showDeleteModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingClientId = null;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingClientId = null;
    }

    public function saveClient(): void
    {
        $validated = $this->validate($this->clientValidationRules());

        $payload = [
            'company_name' => $validated['company_name'],
            'website' => $validated['website'] ?? null,
            'lead_source' => $validated['lead_source'] ?? null,
            'qualification_notes' => $validated['qualification_notes'] ?? null,
            'contacts' => $validated['contacts'] ?? [],
            'social_links' => $validated['social_links'] ?? [],
        ];

        $service = app(ClientService::class);

        if ($this->editingClientId === null) {
            $this->authorize('create', Client::class);
            $service->create($payload);
            Flux::toast(variant: 'success', text: __('Client created.'));
        } else {
            $client = Client::query()->findOrFail($this->editingClientId);
            $this->authorize('update', $client);
            $service->update($client, $payload);
            Flux::toast(variant: 'success', text: __('Client updated.'));
        }

        $this->closeFormModal();
        $this->resetForm();
        unset($this->clients);
    }

    public function archiveClient(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->authorize('update', $client);

        app(ClientService::class)->archive($client);

        Flux::toast(variant: 'success', text: __('Client archived.'));
        unset($this->clients);
    }

    public function ignoreClient(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->authorize('update', $client);

        app(ClientService::class)->ignore($client);

        Flux::toast(variant: 'success', text: __('Client ignored.'));
        unset($this->clients);
    }

    public function markContactIntent(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->authorize('update', $client);

        app(ClientService::class)->markContactIntent($client);

        Flux::toast(variant: 'success', text: __('Contact intent recorded.'));
        unset($this->clients);
    }

    public function deleteClient(): void
    {
        if ($this->deletingClientId === null) {
            return;
        }

        $client = Client::query()->findOrFail($this->deletingClientId);

        $this->authorize('delete', $client);

        try {
            app(ClientService::class)->delete($client);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $errors = $exception->errors();
            $message = $errors['client'][0] ?? __('Unable to delete client.');
            $this->addError('delete', $message);

            return;
        }

        Flux::toast(variant: 'success', text: __('Client deleted.'));
        $this->closeDeleteModal();
        unset($this->clients);
    }

    public function addContactRow(): void
    {
        $this->contacts[] = [
            'name' => '',
            'email' => '',
            'phone' => '',
        ];
    }

    public function removeContactRow(int $index): void
    {
        unset($this->contacts[$index]);
        $this->contacts = array_values($this->contacts);

        if ($this->contacts === []) {
            $this->resetContactRows();
        }
    }

    private function resetForm(): void
    {
        $this->company_name = '';
        $this->website = '';
        $this->lead_source = '';
        $this->qualification_notes = '';
        $this->social_links = [
            'linkedin' => '',
            'twitter' => '',
            'facebook' => '',
        ];
        $this->editingClientId = null;
        $this->resetContactRows();
        $this->resetValidation();
    }

    private function resetContactRows(): void
    {
        $this->contacts = [
            [
                'name' => '',
                'email' => '',
                'phone' => '',
            ],
        ];
    }

    private function fillFormFromClient(Client $client): void
    {
        $this->company_name = $client->company_name;
        $this->website = $client->website ?? '';
        $this->lead_source = $client->lead_source ?? '';
        $this->qualification_notes = $client->qualification_notes ?? '';
        $this->contacts = $client->contacts ?? [];

        if ($this->contacts === []) {
            $this->resetContactRows();
        }

        $links = $client->social_links ?? [];

        $this->social_links = [
            'linkedin' => $links['linkedin'] ?? '',
            'twitter' => $links['twitter'] ?? '',
            'facebook' => $links['facebook'] ?? '',
        ];
    }
}; ?>

<div>
    <div class="space-y-6" data-test="leads-page">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl" class="font-bold text-text-primary">{{ __('Leads / Clients') }}</flux:heading>

            <flux:button variant="primary" wire:click="openCreateModal" data-test="leads-create-button">
                {{ __('New client') }}
            </flux:button>
        </div>

        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    :label="__('Search')"
                    :placeholder="__('Search by company name')"
                    data-test="leads-search"
                />
            </div>

            <div class="w-full md:w-56">
                <flux:select wire:model.live="statusFilter" :label="__('Status')" data-test="leads-status-filter">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    @foreach (ClientStatus::filterable() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table :paginate="$this->clients" class="rounded-lg border border-border-subtle bg-surface">
            <flux:table.columns>
                <flux:table.column>{{ __('Company') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Lead source') }}</flux:table.column>
                <flux:table.column>{{ __('Website') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->clients as $client)
                    <flux:table.row wire:key="client-{{ $client->id }}" class="odd:bg-app/40">
                        <flux:table.cell>
                            <button
                                type="button"
                                class="font-medium text-brand-primary hover:underline"
                                wire:click="openDetailModal({{ $client->id }})"
                                data-test="leads-row-{{ $client->id }}"
                            >
                                {{ $client->company_name }}
                            </button>
                        </flux:table.cell>
                        <flux:table.cell>{{ $client->status->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $client->lead_source ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($client->website)
                                <a href="{{ $client->website }}" target="_blank" rel="noopener noreferrer" class="text-brand-primary hover:underline">
                                    {{ parse_url($client->website, PHP_URL_HOST) ?? $client->website }}
                                </a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-end">
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" data-test="leads-actions-{{ $client->id }}" />
                                <flux:menu>
                                    <flux:menu.item wire:click="openEditModal({{ $client->id }})">{{ __('Edit') }}</flux:menu.item>
                                    <flux:menu.item wire:click="archiveClient({{ $client->id }})">{{ __('Archive') }}</flux:menu.item>
                                    <flux:menu.item wire:click="ignoreClient({{ $client->id }})">{{ __('Ignore') }}</flux:menu.item>
                                    <flux:menu.item wire:click="markContactIntent({{ $client->id }})">{{ __('Contact intent') }}</flux:menu.item>
                                    <flux:menu.item wire:click="openDeleteModal({{ $client->id }})" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-text-secondary">
                            {{ __('No clients found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model.self="showFormModal" class="max-w-2xl" data-test="leads-form-modal">
        <form wire:submit="saveClient" class="space-y-6">
            <flux:heading size="lg">
                @if ($editingClientId === null)
                    {{ __('New client') }}
                @else
                    {{ __('Edit client') }}
                @endif
            </flux:heading>

            <flux:input wire:model="company_name" name="company_name" :label="__('Company name')" required data-test="leads-form-company-name" />

            <div class="space-y-4">
                <flux:subheading>{{ __('Contacts') }}</flux:subheading>

                @foreach ($contacts as $index => $contact)
                    <div class="grid gap-4 rounded-lg border border-border-subtle p-4 md:grid-cols-3" wire:key="contact-{{ $index }}">
                        <flux:input wire:model="contacts.{{ $index }}.name" :label="__('Name')" data-test="leads-form-contact-name-{{ $index }}" />
                        <flux:input wire:model="contacts.{{ $index }}.email" :label="__('Email')" type="email" data-test="leads-form-contact-email-{{ $index }}" />
                        <flux:input wire:model="contacts.{{ $index }}.phone" :label="__('Phone')" data-test="leads-form-contact-phone-{{ $index }}" />
                        @if (count($contacts) > 1)
                            <div class="md:col-span-3">
                                <flux:button type="button" size="sm" variant="ghost" wire:click="removeContactRow({{ $index }})">
                                    {{ __('Remove contact') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endforeach

                <flux:button type="button" size="sm" variant="ghost" wire:click="addContactRow" data-test="leads-form-add-contact">
                    {{ __('Add contact') }}
                </flux:button>
            </div>

            <flux:input wire:model="website" name="website" :label="__('Website')" type="url" data-test="leads-form-website" />

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input wire:model="social_links.linkedin" name="social_links.linkedin" :label="__('LinkedIn')" type="url" />
                <flux:input wire:model="social_links.twitter" name="social_links.twitter" :label="__('Twitter / X')" type="url" />
                <flux:input wire:model="social_links.facebook" name="social_links.facebook" :label="__('Facebook')" type="url" />
            </div>

            <flux:input wire:model="lead_source" name="lead_source" :label="__('Lead source')" data-test="leads-form-lead-source" />

            <flux:textarea wire:model="qualification_notes" name="qualification_notes" :label="__('Qualification notes')" rows="4" data-test="leads-form-qualification-notes" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeFormModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="leads-form-submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model.self="showDetailModal" class="max-w-2xl" data-test="leads-detail-modal">
        @if ($this->viewingClient)
            @php($client = $this->viewingClient)

            <div class="space-y-6">
                <flux:heading size="lg">{{ $client->company_name }}</flux:heading>

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
                    <flux:text class="mt-2 font-light text-text-secondary">
                        {{ __('AI-generated insights will appear here after qualification (wave 4).') }}
                    </flux:text>
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

    <flux:modal wire:model.self="showDeleteModal" class="max-w-lg" data-test="leads-delete-modal">
        <form wire:submit="deleteClient" class="space-y-6">
            <flux:heading size="lg">{{ __('Delete client?') }}</flux:heading>
            <flux:text class="font-light text-text-secondary">
                {{ __('This action soft-deletes the client record.') }}
            </flux:text>

            @error('delete')
                <flux:text class="text-danger">{{ $message }}</flux:text>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="closeDeleteModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="danger" data-test="leads-delete-confirm">{{ __('Delete') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
