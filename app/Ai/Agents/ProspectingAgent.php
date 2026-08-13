<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Support\AgentPromptLoader;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\LeadDeduplicationService;
use App\Services\OpportunityService;

class ProspectingAgent implements AiAgent
{
    public function __construct(
        private readonly DiscoveryAdapter $discoveryAdapter,
        private readonly LeadDeduplicationService $deduplication,
        private readonly ClientService $clients,
        private readonly OpportunityService $opportunities,
        private readonly AgentPromptLoader $promptLoader,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $limit = $this->resolveLimit($context);
        $instructions = $this->promptLoader->load(
            (string) config('prospecting.prompt_path'),
        );

        $discovery = $this->discoveryAdapter->discover([
            'limit' => $limit,
            'instructions' => $instructions,
            'allow_incomplete' => (bool) ($context['allow_incomplete'] ?? false),
        ]);

        $created = [];
        $duplicates = [];
        $skipped = $discovery['skipped'] ?? [];

        foreach ($discovery['leads'] as $lead) {
            if (! is_array($lead)) {
                continue;
            }

            $duplicate = $this->deduplication->findDuplicate([
                'company_name' => $lead['company_name'] ?? $lead['name'] ?? null,
                'website' => $lead['website'] ?? null,
                'email' => $lead['email'] ?? null,
                'phone' => $lead['phone'] ?? null,
            ]);

            if ($duplicate !== null) {
                $duplicates[] = [
                    'company_name' => $lead['company_name'] ?? $lead['name'] ?? null,
                    'matched_client_id' => $duplicate->id,
                ];

                continue;
            }

            $client = $this->createLead($lead);
            $opportunity = $this->opportunities->create([
                'client_id' => $client->id,
                'title' => $this->opportunityTitle($client),
            ]);

            $created[] = [
                'client_id' => $client->id,
                'opportunity_id' => $opportunity->id,
                'company_name' => $client->company_name,
            ];
        }

        return [
            'agent' => 'prospecting',
            'status' => 'completed',
            'target_count' => $discovery['target_count'] ?? $limit,
            'discovered' => count($discovery['leads']),
            'created_count' => count($created),
            'duplicate_count' => count($duplicates),
            'created' => $created,
            'duplicates' => $duplicates,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveLimit(array $context): int
    {
        $configured = config('prospecting.default_limit', 20);
        $limit = $context['limit'] ?? $configured;

        if (! is_int($limit)) {
            if (is_numeric($limit)) {
                $limit = (int) $limit;
            } else {
                $limit = (int) $configured;
            }
        }

        if ($limit < 1) {
            return 1;
        }

        if ($limit > 50) {
            return 50;
        }

        return $limit;
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function createLead(array $lead): Client
    {
        $companyName = trim((string) ($lead['company_name'] ?? $lead['name'] ?? ''));
        $notes = $this->buildQualificationNotes($lead);

        return $this->clients->create([
            'company_name' => $companyName,
            'contact_name' => $lead['contact_name'] ?? null,
            'contact_email' => $lead['email'] ?? null,
            'contact_phone' => $lead['phone'] ?? null,
            'website' => $lead['website'] ?? null,
            'social_links' => $this->mapSocialLinks($lead['social_links'] ?? []),
            'lead_source' => 'prospecting',
            'qualification_notes' => $notes,
            'status' => ClientStatus::Active,
        ]);
    }

    protected function opportunityTitle(Client $client): string
    {
        return $client->company_name.' — Prospecting';
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    protected function buildQualificationNotes(array $lead): ?string
    {
        $parts = [];

        $why = trim((string) ($lead['why_good_fit'] ?? ''));

        if ($why !== '') {
            $parts[] = $why;
        }

        $signals = $lead['observed_signals'] ?? [];

        if (is_array($signals) && $signals !== []) {
            $signalText = collect($signals)
                ->filter(fn (mixed $signal): bool => is_string($signal) && trim($signal) !== '')
                ->map(fn (string $signal): string => trim($signal))
                ->implode('; ');

            if ($signalText !== '') {
                $parts[] = 'Signals: '.$signalText;
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return list<array{platform: string, url: string}>
     */
    protected function mapSocialLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        $mapped = [];

        foreach ($links as $link) {
            if (! is_string($link)) {
                continue;
            }

            $url = trim($link);

            if ($url === '') {
                continue;
            }

            $mapped[] = [
                'platform' => $this->guessSocialPlatform($url),
                'url' => $url,
            ];
        }

        return $mapped;
    }

    protected function guessSocialPlatform(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return 'Web';
        }

        $host = strtolower($host);

        if (str_contains($host, 'instagram')) {
            return 'Instagram';
        }

        if (str_contains($host, 'facebook')) {
            return 'Facebook';
        }

        if (str_contains($host, 'linkedin')) {
            return 'LinkedIn';
        }

        return 'Web';
    }
}
