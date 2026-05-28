<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\PipelineStage;
use App\Models\Client;
use Illuminate\Support\Collection;

class ClientService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Client
    {
        return Client::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Client $client, array $attributes): Client
    {
        $client->update($attributes);

        return $client;
    }

    public function setStatus(Client $client, ClientStatus $status): Client
    {
        $client->status = $status;
        $client->save();

        return $client;
    }

    public function canDelete(Client $client): bool
    {
        return ! $this->hasOpenOpportunities($client);
    }

    public function hasOpenOpportunities(Client $client): bool
    {
        return $client->opportunities()
            ->whereNotIn('stage', [
                PipelineStage::Won->value,
                PipelineStage::Lost->value,
            ])
            ->exists();
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }

    /**
     * @return Collection<int, Client>
     */
    public function listForIndex(?string $search, ?string $statusFilter): Collection
    {
        $query = Client::orderBy('company_name');

        if ($search !== null && $search !== '') {
            $query->whereRaw('lower(company_name) like ?', ['%'.strtolower($search).'%']);
        }

        if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        return $query->get();
    }
}
