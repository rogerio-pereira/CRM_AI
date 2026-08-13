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
        $instructions = trim((string) $rawInstructions);

        if ($instructions === '') {
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

            $rawName = $item['name'] ?? '';
            $rawReason = $item['reason'] ?? '';

            $skipped[] = [
                    'name' => (string) $rawName,
                    'reason' => (string) $rawReason,
                ];
        }

        foreach ($rawLeads as $item) {
            if (! is_array($item)) {
                continue;
            }

            $mappedLead = $this->mapLead($item);

            if ($mappedLead === null) {
                $rawSkippedName = $item['company_name'] ?? '';
                $skippedName = trim((string) $rawSkippedName);

                if ($skippedName === '') {
                    $skippedName = 'Unknown';
                }

                $skipped[] = [
                        'name' => $skippedName,
                        'reason' => 'Missing company name or valid public email.',
                    ];

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
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapLead(array $item): ?array
    {
        $rawCompanyName = $item['company_name'] ?? '';
        $companyName = trim((string) $rawCompanyName);
        $rawEmail = $item['email'] ?? '';
        $trimmedEmail = trim((string) $rawEmail);
        $email = strtolower($trimmedEmail);
        $emailIsValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        if ($companyName === '') {
            return null;
        }

        if ($emailIsValid === false) {
            return null;
        }

        $rawWebsite = $item['website'] ?? null;
        $website = null;

        if (is_string($rawWebsite)) {
            $website = UrlNormalizer::normalize($rawWebsite);
        }

        $socialLinks = $item['social_links'] ?? [];

        if (! is_array($socialLinks)) {
            $socialLinks = [];
        }

        $observedSignals = $item['observed_signals'] ?? [];

        if (! is_array($observedSignals)) {
            $observedSignals = [];
        }

        return [
                'company_name' => $companyName,
                'contact_name' => $item['contact_name'] ?? null,
                'email' => $email,
                'phone' => $item['phone'] ?? null,
                'website' => $website,
                'social_links' => $socialLinks,
                'why_good_fit' => $item['why_good_fit'] ?? null,
                'observed_signals' => $observedSignals,
                'lead_source' => 'prospecting',
            ];
    }
}
