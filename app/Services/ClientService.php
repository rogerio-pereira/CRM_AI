<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\PipelineStage;
use App\Events\ClientCreated;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientService
{
    public const INDEX_PER_PAGE = 20;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Client
    {
        $client = Client::create($attributes);

        ClientCreated::dispatch($client->fresh());

        return $client;
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

    public function paginateForIndex(
        ?string $search,
        ?string $statusFilter,
        int $page = 1,
        int $perPage = self::INDEX_PER_PAGE,
    ): LengthAwarePaginator {
        $query = Client::orderBy('company_name');

        if ($search !== null && $search !== '') {
            $query->whereRaw('lower(company_name) like ?', ['%'.strtolower($search).'%']);
        }

        if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        return $query->paginate(
            perPage: $perPage,
            page: $page,
        );
    }
}
