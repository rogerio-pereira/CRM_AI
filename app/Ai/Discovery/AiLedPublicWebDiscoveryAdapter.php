<?php

namespace App\Ai\Discovery;

use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Tools\FetchPublicPage;
use App\Support\UrlNormalizer;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class AiLedPublicWebDiscoveryAdapter implements DiscoveryAdapter
{
    private const DEFAULT_LIMIT = 20;

    /**
     * @var list<string>
     */
    private const DEFAULT_REGION_PRIORITY = [
        'Lakeland',
        'Tampa',
        'Orlando',
        'Wesley Chapel',
        'Sarasota',
    ];

    public function __construct(
        private readonly ProspectingDiscoveryAgent $discoveryAgent,
        private readonly FetchPublicPage $fetchPublicPage,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function discover(array $options = []): array
    {
        $limit = $this->resolveLimit($options);
        $allowIncomplete = (bool) ($options['allow_incomplete'] ?? false);
        $regionPriority = $this->resolveRegionPriority($options);
        $agent = $this->resolveAgent($options);

        $prompt = $this->buildUserPrompt($limit, $regionPriority, $allowIncomplete);

        $response = $agent->prompt($prompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Prospecting discovery did not return structured output.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray();

        return $this->normalizePayload($payload, $limit, $regionPriority, $allowIncomplete);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function resolveLimit(array $options): int
    {
        $limit = $options['limit'] ?? self::DEFAULT_LIMIT;

        if (! is_int($limit)) {
            return self::DEFAULT_LIMIT;
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
     * @param  array<string, mixed>  $options
     * @return list<string>
     */
    protected function resolveRegionPriority(array $options): array
    {
        $regions = $options['region_priority'] ?? self::DEFAULT_REGION_PRIORITY;

        if (! is_array($regions)) {
            return self::DEFAULT_REGION_PRIORITY;
        }

        $normalized = [];

        foreach ($regions as $region) {
            if (! is_string($region)) {
                continue;
            }

            $trimmed = trim($region);

            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        if ($normalized === []) {
            return self::DEFAULT_REGION_PRIORITY;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function resolveAgent(array $options): ProspectingDiscoveryAgent
    {
        $instructions = $options['instructions'] ?? null;

        if (! is_string($instructions) || trim($instructions) === '') {
            return $this->discoveryAgent;
        }

        return new ProspectingDiscoveryAgent(
            fetchPublicPage: $this->fetchPublicPage,
            instructions: $instructions,
        );
    }

    /**
     * @param  list<string>  $regionPriority
     */
    protected function buildUserPrompt(int $limit, array $regionPriority, bool $allowIncomplete): string
    {
        $regions = implode(', ', $regionPriority);
        $incompleteRule = 'Only return leads that include a public email address.';

        if ($allowIncomplete) {
            $incompleteRule = 'Email is preferred but incomplete leads without email are allowed for this run.';
        }

        return "Discover up to {$limit} lead candidates.\n"
            ."Region priority: {$regions}.\n"
            ."{$incompleteRule}\n"
            .'Return structured prospecting JSON only.';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $regionPriority
     * @return array{
     *     schema_version: int,
     *     agent: string,
     *     target_count: int,
     *     region_priority: list<string>,
     *     leads: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     * }
     */
    protected function normalizePayload(
        array $payload,
        int $limit,
        array $regionPriority,
        bool $allowIncomplete,
    ): array {
        $rawLeads = $payload['leads'] ?? [];
        $rawSkipped = $payload['skipped'] ?? [];

        if (! is_array($rawLeads)) {
            $rawLeads = [];
        }

        if (! is_array($rawSkipped)) {
            $rawSkipped = [];
        }

        $leads = [];
        $skipped = [];

        foreach ($rawSkipped as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $reason = trim((string) ($item['reason'] ?? ''));

            if ($name === '' || $reason === '') {
                continue;
            }

            $skipped[] = [
                'name' => $name,
                'reason' => $reason,
            ];
        }

        foreach ($rawLeads as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalizedLead = $this->normalizeLead($item);

            if ($normalizedLead === null) {
                continue;
            }

            $email = $normalizedLead['email'] ?? null;

            if (! $allowIncomplete && ($email === null || $email === '')) {
                $skipped[] = [
                    'name' => (string) ($normalizedLead['name'] ?? 'Unknown'),
                    'reason' => 'Missing public email address.',
                ];

                continue;
            }

            $leads[] = $normalizedLead;

            if (count($leads) >= $limit) {
                break;
            }
        }

        return [
            'schema_version' => 1,
            'agent' => 'prospecting',
            'target_count' => $limit,
            'region_priority' => $regionPriority,
            'leads' => $leads,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function normalizeLead(array $item): ?array
    {
        $name = trim((string) ($item['name'] ?? ''));
        $companyName = trim((string) ($item['company_name'] ?? ''));

        if ($name === '' && $companyName === '') {
            return null;
        }

        if ($name === '') {
            $name = $companyName;
        }

        if ($companyName === '') {
            $companyName = $name;
        }

        $email = $this->normalizeEmail($item['email'] ?? null);
        $website = UrlNormalizer::normalize(
            isset($item['website']) ? (string) $item['website'] : null,
        );

        return [
            'name' => $name,
            'company_name' => $companyName,
            'contact_name' => $this->nullableString($item['contact_name'] ?? null),
            'email' => $email,
            'phone' => $this->nullableString($item['phone'] ?? null),
            'website' => $website,
            'social_links' => $this->normalizeStringList($item['social_links'] ?? []),
            'city' => $this->nullableString($item['city'] ?? null),
            'state' => $this->nullableString($item['state'] ?? null),
            'lead_source' => 'prospecting',
            'source_urls' => $this->normalizeStringList($item['source_urls'] ?? []),
            'observed_signals' => $this->normalizeStringList($item['observed_signals'] ?? []),
            'likely_needs' => $this->normalizeStringList($item['likely_needs'] ?? []),
            'why_good_fit' => $this->nullableString($item['why_good_fit'] ?? null),
            'confidence' => $this->nullableString($item['confidence'] ?? null),
        ];
    }

    protected function normalizeEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $trimmed = trim($email);

        if ($trimmed === '') {
            return null;
        }

        $validated = filter_var($trimmed, FILTER_VALIDATE_EMAIL);

        if ($validated === false) {
            return null;
        }

        return strtolower($validated);
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    /**
     * @return list<string>
     */
    protected function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $entry) {
            if (! is_string($entry)) {
                continue;
            }

            $trimmed = trim($entry);

            if ($trimmed === '') {
                continue;
            }

            $items[] = $trimmed;
        }

        return $items;
    }
}
