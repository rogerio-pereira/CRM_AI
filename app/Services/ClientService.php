<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\OpportunityStage;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ClientService
{
    /**
     * @return LengthAwarePaginator<int, Client>
     */
    public function list(?string $search = null, ?ClientStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query()->latest();

        if ($search !== null && $search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->whereRaw('LOWER(company_name) LIKE ?', [$needle]);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        $payload = $this->normalizePayload($data);
        $payload['status'] = ClientStatus::Active;

        return Client::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        $client->fill($this->normalizePayload($data));
        $client->save();

        return $client;
    }

    public function archive(Client $client): Client
    {
        $client->status = ClientStatus::Archived;
        $client->save();

        return $client;
    }

    public function ignore(Client $client): Client
    {
        $client->status = ClientStatus::Ignored;
        $client->save();

        return $client;
    }

    public function markContactIntent(Client $client): Client
    {
        $client->status = ClientStatus::ContactIntent;
        $client->save();

        return $client;
    }

    public function delete(Client $client): void
    {
        if ($this->hasBlockingOpportunity($client)) {
            throw ValidationException::withMessages([
                'client' => [__('This client cannot be deleted while an open opportunity exists.')],
            ]);
        }

        $client->delete();
    }

    public function hasBlockingOpportunity(Client $client): bool
    {
        return $client->opportunities()
            ->whereNotIn('stage', [
                OpportunityStage::Won->value,
                OpportunityStage::Lost->value,
            ])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $payload = [
            'company_name' => $data['company_name'],
            'website' => $data['website'] ?? null,
            'lead_source' => $data['lead_source'] ?? null,
            'qualification_notes' => $data['qualification_notes'] ?? null,
            'contacts' => $this->normalizeContacts($data['contacts'] ?? []),
            'social_links' => $this->normalizeSocialLinks($data['social_links'] ?? []),
        ];

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $contacts
     * @return list<array{name: string, email: string|null, phone: string|null}>
     */
    private function normalizeContacts(array $contacts): array
    {
        $normalized = [];

        foreach ($contacts as $contact) {
            $name = trim((string) ($contact['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'email' => $this->nullableString($contact['email'] ?? null),
                'phone' => $this->nullableString($contact['phone'] ?? null),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $socialLinks
     * @return array<string, string>
     */
    private function normalizeSocialLinks(array $socialLinks): array
    {
        $normalized = [];

        foreach (['linkedin', 'twitter', 'facebook'] as $platform) {
            $url = $this->nullableString($socialLinks[$platform] ?? null);

            if ($url !== null) {
                $normalized[$platform] = $url;
            }
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }
}
