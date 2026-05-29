<?php

namespace App\Livewire\Leads;

use App\Enums\ClientStatus;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Support\UrlNormalizer;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Leads / Clients')]
class Index extends Component
{
    use WithPagination;

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

    public function getClientsProperty(): LengthAwarePaginator
    {
        return app(ClientService::class)->paginateForIndex(
            $this->search !== '' ? $this->search : null,
            $this->statusFilter !== 'all' ? $this->statusFilter : null,
            page: $this->getPage(),
        );
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
    public function detailClient(): ?Client
    {
        if ($this->detailClientId === null) {
            return null;
        }

        return Client::with('opportunities')->find($this->detailClientId);
    }

    #[Computed]
    public function deleteClient(): ?Client
    {
        if ($this->deleteClientId === null) {
            return null;
        }

        return Client::find($this->deleteClientId);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingClientId = null;
        $this->showFormModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        $client = Client::findOrFail($clientId);

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
        $this->normalizeUrlsBeforeValidation();

        $validated = $this->validate(ClientRequest::formRules());

        $attributes = $validated;
        $attributes['social_links'] = $this->filteredSocialLinks();

        if ($this->editingClientId === null) {
            $attributes['status'] = ClientStatus::Active;
            $clientService->create($attributes);
            Flux::toast(variant: 'success', text: __('Lead created.'));
        } else {
            $client = Client::findOrFail($this->editingClientId);
            $clientService->update($client, $attributes);
            Flux::toast(variant: 'success', text: __('Lead updated.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function setContactIntent(int $clientId, ClientService $clientService): void
    {
        $client = Client::findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::ContactIntent);
        Flux::toast(variant: 'success', text: __('Marked as contact intent.'));
    }

    public function setIgnored(int $clientId, ClientService $clientService): void
    {
        $client = Client::findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::Ignored);
        Flux::toast(variant: 'success', text: __('Lead ignored.'));
    }

    public function setArchived(int $clientId, ClientService $clientService): void
    {
        $client = Client::findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::Archived);
        Flux::toast(variant: 'success', text: __('Lead archived.'));
    }

    public function setActive(int $clientId, ClientService $clientService): void
    {
        $client = Client::findOrFail($clientId);
        $clientService->setStatus($client, ClientStatus::Active);
        Flux::toast(variant: 'success', text: __('Lead marked as active.'));
    }

    public function confirmDelete(ClientService $clientService): void
    {
        if ($this->deleteClientId === null) {
            return;
        }

        $client = Client::findOrFail($this->deleteClientId);

        if (! $clientService->canDelete($client)) {
            $this->addError('delete', __('Cannot delete a lead with open opportunities.'));

            return;
        }

        $clientService->delete($client);
        Flux::toast(variant: 'success', text: __('Lead deleted.'));
        $this->showDeleteModal = false;
        $this->deleteClientId = null;
        unset($this->deleteClient);
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

    private function normalizeUrlsBeforeValidation(): void
    {
        $website = UrlNormalizer::normalize($this->website);

        if ($website === null) {
            $this->website = '';
        } else {
            $this->website = $website;
        }

        foreach ($this->social_links as $index => $link) {
            $url = UrlNormalizer::normalize($link['url'] ?? null);

            if ($url === null) {
                $this->social_links[$index]['url'] = '';
            } else {
                $this->social_links[$index]['url'] = $url;
            }
        }
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
