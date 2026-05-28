<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\ClientAiInsight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    /**
     * @return LengthAwarePaginator<int, Client>
     */
    public function paginate(?string $search = null, ?ClientStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query()->with(['contacts', 'aiInsight'])->latest();

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
    public function store(array $data): Client
    {
        return DB::transaction(function () use ($data): Client {
            $client = Client::query()->create([
                'company_name' => $data['company_name'],
                'website' => $data['website'] ?? null,
                'lead_source' => $data['lead_source'] ?? null,
                'qualification_notes' => $data['qualification_notes'] ?? null,
                'social_links' => $this->normalizeSocialLinks($data['social_links'] ?? []),
                'status' => ClientStatus::Active,
            ]);

            $this->syncContacts($client, $data['contacts'] ?? []);
            $this->ensureAiInsightRecord($client);

            return $client->load(['contacts', 'aiInsight']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data): Client {
            $client->fill([
                'company_name' => $data['company_name'],
                'website' => $data['website'] ?? null,
                'lead_source' => $data['lead_source'] ?? null,
                'qualification_notes' => $data['qualification_notes'] ?? null,
                'social_links' => $this->normalizeSocialLinks($data['social_links'] ?? []),
            ]);
            $client->save();

            $this->syncContacts($client, $data['contacts'] ?? []);
            $this->ensureAiInsightRecord($client);

            return $client->load(['contacts', 'aiInsight']);
        });
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

    public function destroy(Client $client): void
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
        if (! method_exists($client, 'opportunities')) {
            return false;
        }

        return $client->opportunities()
            ->whereNotIn('stage', ['won', 'lost'])
            ->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>  $contacts
     */
    private function syncContacts(Client $client, array $contacts): void
    {
        $client->contacts()->delete();

        foreach ($this->normalizeContacts($contacts) as $contact) {
            $client->contacts()->create($contact);
        }
    }

    private function ensureAiInsightRecord(Client $client): void
    {
        if ($client->aiInsight()->exists()) {
            return;
        }

        ClientAiInsight::query()->create([
            'client_id' => $client->id,
            'summary' => null,
        ]);
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
