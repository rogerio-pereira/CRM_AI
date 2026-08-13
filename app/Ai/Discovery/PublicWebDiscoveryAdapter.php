<?php

namespace App\Ai\Discovery;

use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Tools\FetchPublicPage;
use App\Support\UrlNormalizer;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class PublicWebDiscoveryAdapter implements DiscoveryAdapter
{
    public function __construct(
        private readonly FetchPublicPage $fetchPublicPage,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function discover(array $options = []): array
    {
        $limit = (int) ($options['limit'] ?? config('prospecting.default_limit', 20));

        if ($limit < 1) {
            $limit = 1;
        }

        $instructions = (string) ($options['instructions'] ?? '');

        if (trim($instructions) === '') {
            throw new RuntimeException('Prospecting discovery requires approved prompt instructions.');
        }

        $agent = new ProspectingDiscoveryAgent(
            $this->fetchPublicPage,
            $instructions,
        );

        $userPrompt = "Discover up to {$limit} lead candidates with a public email. Return structured JSON only.";

        $response = $agent->prompt($userPrompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Prospecting discovery did not return structured output.');
        }

        $payload = $response->toArray();
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

            $skipped[] = [
                'name' => (string) ($item['name'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        }

        foreach ($rawLeads as $item) {
            if (! is_array($item)) {
                continue;
            }

            $companyName = trim((string) ($item['company_name'] ?? ''));
            $email = strtolower(trim((string) ($item['email'] ?? '')));

            if ($companyName === '' || $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $skipped[] = [
                    'name' => $companyName !== '' ? $companyName : 'Unknown',
                    'reason' => 'Missing company name or valid public email.',
                ];

                continue;
            }

            $websiteInput = null;

            if (isset($item['website']) && is_string($item['website'])) {
                $websiteInput = $item['website'];
            }

            $website = UrlNormalizer::normalize($websiteInput);

            $leads[] = [
                'company_name' => $companyName,
                'contact_name' => $this->nullableString($item['contact_name'] ?? null),
                'email' => $email,
                'phone' => $this->nullableString($item['phone'] ?? null),
                'website' => $website,
                'social_links' => $this->stringList($item['social_links'] ?? []),
                'why_good_fit' => $this->nullableString($item['why_good_fit'] ?? null),
                'observed_signals' => $this->stringList($item['observed_signals'] ?? []),
                'lead_source' => 'prospecting',
            ];

            if (count($leads) >= $limit) {
                break;
            }
        }

        return [
            'leads' => $leads,
            'skipped' => $skipped,
        ];
    }

    private function nullableString(mixed $value): ?string
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
    private function stringList(mixed $value): array
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
