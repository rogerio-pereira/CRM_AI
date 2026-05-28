<?php

namespace App\Livewire\Leads;

use App\Concerns\ClientValidationRules;
use App\Enums\ClientStatus;
use App\Http\Controllers\ClientController;
use App\Models\Client;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Leads / Clients')]
class Index extends Component
{
    use ClientValidationRules, WithPagination;

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

        return app(ClientController::class)->paginate(
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

        return Client::query()
            ->with(['contacts', 'aiInsight'])
            ->find($this->viewingClientId);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingClientId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Client::query()->with('contacts')->findOrFail($clientId);

        $this->fillFormFromClient($client);
        $this->editingClientId = $client->id;
        $this->showFormModal = true;
    }

    public function openDetailModal(int $clientId): void
    {
        $this->viewingClientId = $clientId;
        $this->showDetailModal = true;
    }

    public function openDeleteModal(int $clientId): void
    {
        $this->deletingClientId = $clientId;
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

        $controller = app(ClientController::class);

        if ($this->editingClientId === null) {
            $controller->store($payload);
            Flux::toast(variant: 'success', text: __('Client created.'));
        } else {
            $client = Client::query()->findOrFail($this->editingClientId);
            $controller->update($client, $payload);
            Flux::toast(variant: 'success', text: __('Client updated.'));
        }

        $this->closeFormModal();
        $this->resetForm();
        unset($this->clients);
    }

    public function archiveClient(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        app(ClientController::class)->archive($client);

        Flux::toast(variant: 'success', text: __('Client archived.'));
        unset($this->clients);
    }

    public function ignoreClient(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        app(ClientController::class)->ignore($client);

        Flux::toast(variant: 'success', text: __('Client ignored.'));
        unset($this->clients);
    }

    public function markContactIntent(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        app(ClientController::class)->markContactIntent($client);

        Flux::toast(variant: 'success', text: __('Contact intent recorded.'));
        unset($this->clients);
    }

    public function deleteClient(): void
    {
        if ($this->deletingClientId === null) {
            return;
        }

        $client = Client::query()->findOrFail($this->deletingClientId);

        try {
            app(ClientController::class)->destroy($client);
        } catch (ValidationException $exception) {
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

    public function render()
    {
        return view('livewire.leads.index');
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

        $this->contacts = $client->contacts
            ->map(fn ($contact): array => [
                'name' => $contact->name,
                'email' => $contact->email ?? '',
                'phone' => $contact->phone ?? '',
            ])
            ->values()
            ->all();

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
}
