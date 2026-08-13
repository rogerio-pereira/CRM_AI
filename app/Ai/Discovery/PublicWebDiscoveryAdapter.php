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
        $defaultLimit = (int) config('prospecting.default_limit', 20);
        $requestedLimit = $options['limit'] ?? $defaultLimit;
        $limit = (int) $requestedLimit;

        if ($limit < 1) {
            $limit = 1;
        }

        $rawInstructions = $options['instructions'] ?? '';
        $instructions = (string) $rawInstructions;
        $trimmedInstructions = trim($instructions);

        if ($trimmedInstructions === '') {
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
        $skipped = $this->mapSkippedItems($rawSkipped);

        foreach ($rawLeads as $item) {
            if (! is_array($item)) {
                continue;
            }

            $mappedLead = $this->mapLead($item);

            if ($mappedLead === null) {
                $skipped[] = $this->skippedInvalidLead($item);

                continue;
            }

            $leads[] = $mappedLead;

            if (count($leads) >= $limit) {
                break;
            }
        }

        return [
                'leads' => $leads,
                'skipped' => $skipped,
            ];
    }

    /**
     * @param  list<mixed>  $rawSkipped
     * @return list<array{name: string, reason: string}>
     */
    private function mapSkippedItems(array $rawSkipped): array
    {
        $skipped = [];

        foreach ($rawSkipped as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rawName = $item['name'] ?? '';
            $rawReason = $item['reason'] ?? '';

            $skipped[] = [
                    'name' => (string) $rawName,
                    'reason' => (string) $rawReason,
                ];
        }

        return $skipped;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapLead(array $item): ?array
    {
        $rawCompanyName = $item['company_name'] ?? '';
        $companyName = trim((string) $rawCompanyName);
        $rawEmail = $item['email'] ?? '';
        $email = strtolower(trim((string) $rawEmail));
        $emailIsValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        if ($companyName === '') {
            return null;
        }

        if ($emailIsValid === false) {
            return null;
        }

        $websiteInput = null;

        if (isset($item['website'])) {
            if (is_string($item['website'])) {
                $websiteInput = $item['website'];
            }
        }

        $website = UrlNormalizer::normalize($websiteInput);
        $rawContactName = $item['contact_name'] ?? null;
        $rawPhone = $item['phone'] ?? null;
        $rawWhyGoodFit = $item['why_good_fit'] ?? null;
        $rawSocialLinks = $item['social_links'] ?? [];
        $rawObservedSignals = $item['observed_signals'] ?? [];
        $contactName = $this->nullableString($rawContactName);
        $phone = $this->nullableString($rawPhone);
        $whyGoodFit = $this->nullableString($rawWhyGoodFit);
        $socialLinks = $this->stringList($rawSocialLinks);
        $observedSignals = $this->stringList($rawObservedSignals);

        return [
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'email' => $email,
                'phone' => $phone,
                'website' => $website,
                'social_links' => $socialLinks,
                'why_good_fit' => $whyGoodFit,
                'observed_signals' => $observedSignals,
                'lead_source' => 'prospecting',
            ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{name: string, reason: string}
     */
    private function skippedInvalidLead(array $item): array
    {
        $rawCompanyName = $item['company_name'] ?? '';
        $companyName = trim((string) $rawCompanyName);
        $skippedName = 'Unknown';

        if ($companyName !== '') {
            $skippedName = $companyName;
        }

        return [
                'name' => $skippedName,
                'reason' => 'Missing company name or valid public email.',
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
