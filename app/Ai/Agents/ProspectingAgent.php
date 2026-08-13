<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Contracts\DiscoveryAdapter;
use App\Enums\ClientStatus;
use App\Services\ClientService;
use App\Services\LeadDeduplicationService;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ProspectingAgent implements AiAgent
{
    private const APPROVED_PROMPT_PATH = 'docs/prompts/prospecting-agent.md';

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
        $defaultLimit = (int) config('prospecting.default_limit', 20);
        $requestedLimit = $context['limit'] ?? $defaultLimit;
        $limit = (int) $requestedLimit;
        $instructions = $this->loadApprovedPrompt();

        $discovery = $this->discovery
                            ->discover([
                                'limit' => $limit,
                                'instructions' => $instructions,
                            ]);

        $created = [];
        $duplicates = [];

        foreach ($discovery['leads'] as $lead) {
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
                $duplicates[] = [
                        'company_name' => $companyName,
                        'matched_client_id' => $duplicate->id,
                    ];

                continue;
            }

            $created[] = $this->createLeadAndOpportunity($lead, $companyName);
        }

        $createdCount = count($created);
        $duplicateCount = count($duplicates);

        return [
                'agent' => 'prospecting',
                'status' => 'completed',
                'created_count' => $createdCount,
                'duplicate_count' => $duplicateCount,
                'created' => $created,
                'duplicates' => $duplicates,
                'skipped' => $discovery['skipped'],
            ];
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
