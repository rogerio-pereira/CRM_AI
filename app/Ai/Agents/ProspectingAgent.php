<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Contracts\DiscoveryAdapter;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\LeadDeduplicationService;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ProspectingAgent implements AiAgent
{
    private const APPROVED_PROMPT_PATH = 'docs/prompts/prospecting-agent.md';

    private const MAX_DISCOVERY_ATTEMPTS = 5;

    public function __construct(
        private readonly DiscoveryAdapter $discovery,
        private readonly LeadDeduplicationService $deduplication,
        private readonly ClientService $clients,
        private readonly OpportunityService $opportunities,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $instructions = $this->loadApprovedPrompt();
        $excludeCompanyNames = Client::orderBy('company_name')
                                    ->pluck('company_name')
                                    ->all();
        $attempt = 0;

        while ($attempt < self::MAX_DISCOVERY_ATTEMPTS) {
            $attempt++;

            $discovery = $this->discovery
                                ->discover([
                                    'limit' => 1,
                                    'instructions' => $instructions,
                                    'exclude_company_names' => $excludeCompanyNames,
                                ]);

            $leads = $discovery['leads'];
            $lead = $leads[0] ?? null;

            if ($lead === null) {
                $excludeCompanyNames = $this->appendExcludedNames(
                    $excludeCompanyNames,
                    $discovery['skipped'],
                );

                continue; // Goes back to while
            }

            $rawCompanyName = $lead['company_name'] ?? '';
            $companyName = (string) $rawCompanyName;
            $website = $lead['website'] ?? null;
            $email = $lead['email'] ?? null;
            $phone = $lead['phone'] ?? null;
            $candidate = [
                    'company_name' => $companyName,
                    'website' => $website,
                    'email' => $email,
                    'phone' => $phone,
                ];

            $duplicate = $this->deduplication
                                ->findDuplicate($candidate);

            if ($duplicate !== null) {
                $excludeCompanyNames[] = $companyName;

                continue; // Goes back to while
            }

            $created = $this->createLeadAndOpportunity($lead, $companyName);

            return [
                    'agent' => 'prospecting',
                    'status' => 'completed',
                    'created_count' => 1,
                    'created' => [$created],
                ];
        }

        throw new RuntimeException('Prospecting could not find a unique contactable lead.');
    }

    /**
     * @param  array<string, mixed>  $lead
     * @return array{client_id: int, opportunity_id: int, company_name: string}
     */
    private function createLeadAndOpportunity(array $lead, string $companyName): array
    {
        $socialLinks = $this->mapSocialLinks($lead);
        $notes = $lead['why_good_fit'] ?? null;
        $contactName = $lead['contact_name'] ?? null;
        $email = $lead['email'] ?? null;
        $phone = $lead['phone'] ?? null;
        $website = $lead['website'] ?? null;

        $clientAttributes = [
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'contact_email' => $email,
                'contact_phone' => $phone,
                'website' => $website,
                'social_links' => $socialLinks,
                'lead_source' => 'prospecting',
                'qualification_notes' => $notes,
                'status' => ClientStatus::Active,
            ];

        $client = $this->clients
                        ->create($clientAttributes);

        $opportunityTitle = $client->company_name;

        $opportunityAttributes = [
                'client_id' => $client->id,
                'title' => $opportunityTitle,
            ];

        $opportunity = $this->opportunities
                            ->create($opportunityAttributes);

        return [
                'client_id' => $client->id,
                'opportunity_id' => $opportunity->id,
                'company_name' => $client->company_name,
            ];
    }

    /**
     * @param  array<string, mixed>  $lead
     * @return list<array{platform: string, url: string}>
     */
    private function mapSocialLinks(array $lead): array
    {
        $discoveredSocialLinks = $lead['social_links'] ?? [];

        if (! is_array($discoveredSocialLinks)) {
            return [];
        }

        $socialLinks = [];

        foreach ($discoveredSocialLinks as $url) {
            if (! is_string($url)) {
                continue;
            }

            $trimmedUrl = trim($url);

            if ($trimmedUrl === '') {
                continue;
            }

            $socialLinks[] = [
                    'platform' => 'Web',
                    'url' => $trimmedUrl,
                ];
        }

        return $socialLinks;
    }

    /**
     * @param  list<string>  $excludeCompanyNames
     * @param  list<array<string, mixed>>  $skipped
     * @return list<string>
     */
    private function appendExcludedNames(array $excludeCompanyNames, array $skipped): array
    {
        foreach ($skipped as $item) {
            $rawName = $item['name'] ?? '';
            $name = trim((string) $rawName);

            if ($name === '') {
                continue;
            }

            $excludeCompanyNames[] = $name;
        }

        return $excludeCompanyNames;
    }

    private function loadApprovedPrompt(): string
    {
        $path = base_path(self::APPROVED_PROMPT_PATH);

        if (! File::exists($path)) {
            throw new RuntimeException('Prospecting prompt file not found: '.$path);
        }

        $contents = File::get($path);
        $prompt = trim((string) $contents);

        if ($prompt === '') {
            throw new RuntimeException('Prospecting prompt file is empty: '.$path);
        }

        return $prompt;
    }
}
