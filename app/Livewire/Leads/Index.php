<?php

namespace App\Livewire\Leads;

use App\Enums\ClientStatus;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Leads / Clients')]
class Index extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showFormModal = false;

    public bool $showDetailModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingClientId = null;

    public ?int $detailClientId = null;

    public ?int $deleteClientId = null;

    public string $company_name = '';

    public string $contact_name = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $website = '';

    public string $lead_source = '';

    public string $qualification_notes = '';

    /**
     * @var list<array{platform: string, url: string}>
     */
    public array $social_links = [];

    #[Computed]
    public function clients(): Collection
    {
        return app(ClientService::class)->listForIndex(
            $this->search !== '' ? $this->search : null,
            $this->statusFilter !== 'all' ? $this->statusFilter : null,
        );
    }

    #[Computed]
    public function detailClient(): ?Client
    {
        if ($this->detailClientId === null) {
            return null;
        }

        return Client::query()
            ->with('opportunities')
            ->find($this->detailClientId);
    }

    #[Computed]
    public function deleteClient(): ?Client
    {
        if ($this->deleteClientId === null) {
            return null;
        }

        return Client::query()->find($this->deleteClientId);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingClientId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        $this->editingClientId = $client->id;
        $this->company_name = $client->company_name;
        $this->contact_name = $client->contact_name ?? '';
        $this->contact_email = $client->contact_email ?? '';
        $this->contact_phone = $client->contact_phone ?? '';
        $this->website = $client->website ?? '';
        $this->lead_source = $client->lead_source ?? '';
        $this->qualification_notes = $client->qualification_notes ?? '';
        $this->social_links = $this->normalizeSocialLinks($client->social_links);
        $this->showFormModal = true;
    }

    public function openDetailModal(int $clientId): void
    {
        $this->detailClientId = $clientId;
        $this->showDetailModal = true;
        unset($this->detailClient);
    }

    public function openDeleteModal(int $clientId): void
    {
        $this->deleteClientId = $clientId;
        $this->showDeleteModal = true;
        unset($this->deleteClient);
    }

    public function addSocialLinkRow(): void
    {
        $this->social_links[] = [
            'platform' => '',
            'url' => '',
        ];
    }

    public function removeSocialLinkRow(int $index): void
    {
        if (! array_key_exists($index, $this->social_links)) {
            return;
        }

        unset($this->social_links[$index]);
        $this->social_links = array_values($this->social_links);
    }

    public function saveClient(ClientService $clientService): void
    {
        $validated = $this->validate(ClientRequest::formRules());

        $attributes = $validated;
        $attributes['social_links'] = $this->filteredSocialLinks();

        if ($this->editingClientId === null) {
            $attributes['status'] = ClientStatus::Active;
            $clientService->create($attributes);
            Flux::toast(variant: 'success', text: __('Lead created.'));
        } else {
            $client = Client::query()->findOrFail($this->editingClientId);
            $clientService->update($client, $attributes);
            Flux::toast(variant: 'success', text: __('Lead updated.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
        unset($this->clients);
    }

    public function setContactIntent(int $clientId, ClientService $clientService): void
    {
        $client = Client::query()->findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::ContactIntent);
        Flux::toast(variant: 'success', text: __('Marked as contact intent.'));
        unset($this->clients);
    }

    public function setIgnored(int $clientId, ClientService $clientService): void
    {
        $client = Client::query()->findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::Ignored);
        Flux::toast(variant: 'success', text: __('Lead ignored.'));
        unset($this->clients);
    }

    public function setArchived(int $clientId, ClientService $clientService): void
    {
        $client = Client::query()->findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::Archived);
        Flux::toast(variant: 'success', text: __('Lead archived.'));
        unset($this->clients);
    }

    public function confirmDelete(ClientService $clientService): void
    {
        if ($this->deleteClientId === null) {
            return;
        }

        $client = Client::query()->findOrFail($this->deleteClientId);

        if (! $clientService->canDelete($client)) {
            $this->addError('delete', __('Cannot delete a lead with open opportunities.'));

            return;
        }

        $clientService->delete($client);
        Flux::toast(variant: 'success', text: __('Lead deleted.'));
        $this->showDeleteModal = false;
        $this->deleteClientId = null;
        unset($this->clients, $this->deleteClient);
    }

    public function render(): View
    {
        return view('livewire.leads.index');
    }

    private function resetForm(): void
    {
        $this->company_name = '';
        $this->contact_name = '';
        $this->contact_email = '';
        $this->contact_phone = '';
        $this->website = '';
        $this->lead_source = '';
        $this->qualification_notes = '';
        $this->social_links = [
            [
                'platform' => '',
                'url' => '',
            ],
        ];
        $this->resetValidation();
    }

    /**
     * @param  array<int, array<string, string>>|null  $links
     * @return list<array{platform: string, url: string}>
     */
    private function normalizeSocialLinks(?array $links): array
    {
        if ($links === null || $links === []) {
            return [
                [
                    'platform' => '',
                    'url' => '',
                ],
            ];
        }

        $normalized = [];

        foreach ($links as $link) {
            $normalized[] = [
                'platform' => $link['platform'] ?? '',
                'url' => $link['url'] ?? '',
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{platform: string, url: string}>
     */
    private function filteredSocialLinks(): array
    {
        $filtered = [];

        foreach ($this->social_links as $link) {
            $platform = trim($link['platform'] ?? '');
            $url = trim($link['url'] ?? '');

            if ($platform === '' && $url === '') {
                continue;
            }

            $filtered[] = [
                'platform' => $platform,
                'url' => $url,
            ];
        }

        return $filtered;
    }
}
