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
        $limit = (int) ($context['limit'] ?? config('prospecting.default_limit', 20));
        $instructions = $this->loadApprovedPrompt();

        $discovery = $this->discovery->discover([
            'limit' => $limit,
            'instructions' => $instructions,
        ]);

        $created = [];
        $duplicates = [];

        foreach ($discovery['leads'] as $lead) {
            $companyName = (string) ($lead['company_name'] ?? '');

            $duplicate = $this->deduplication->findDuplicate([
                'company_name' => $companyName,
                'website' => $lead['website'] ?? null,
                'email' => $lead['email'] ?? null,
                'phone' => $lead['phone'] ?? null,
            ]);

            if ($duplicate !== null) {
                $duplicates[] = [
                    'company_name' => $companyName,
                    'matched_client_id' => $duplicate->id,
                ];

                continue;
            }

            $socialLinks = [];

            foreach ($lead['social_links'] ?? [] as $url) {
                if (! is_string($url) || trim($url) === '') {
                    continue;
                }

                $socialLinks[] = [
                    'platform' => 'Web',
                    'url' => trim($url),
                ];
            }

            $notes = $lead['why_good_fit'] ?? null;

            if (is_string($notes)) {
                $notes = trim($notes);

                if ($notes === '') {
                    $notes = null;
                }
            } else {
                $notes = null;
            }

            $client = $this->clients->create([
                'company_name' => $companyName,
                'contact_name' => $lead['contact_name'] ?? null,
                'contact_email' => $lead['email'] ?? null,
                'contact_phone' => $lead['phone'] ?? null,
                'website' => $lead['website'] ?? null,
                'social_links' => $socialLinks,
                'lead_source' => 'prospecting',
                'qualification_notes' => $notes,
                'status' => ClientStatus::Active,
            ]);

            $opportunityTitle = $client->company_name.' — Prospecting';

            $opportunity = $this->opportunities->create([
                'client_id' => $client->id,
                'title' => $opportunityTitle,
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
            'created_count' => count($created),
            'duplicate_count' => count($duplicates),
            'created' => $created,
            'duplicates' => $duplicates,
            'skipped' => $discovery['skipped'],
        ];
    }

    private function loadApprovedPrompt(): string
    {
        $relativePath = (string) config('prospecting.prompt_path');
        $path = base_path($relativePath);

        if (! File::exists($path)) {
            throw new RuntimeException('Prospecting prompt file not found: '.$path);
        }

        $contents = File::get($path);

        if (! is_string($contents) || trim($contents) === '') {
            throw new RuntimeException('Prospecting prompt file is empty: '.$path);
        }

        if (preg_match('/^##\s+System Prompt\s*$/m', $contents, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $start = $matches[0][1] + strlen($matches[0][0]);
            $section = trim(substr($contents, $start));

            if ($section !== '') {
                return $section;
            }
        }

        return trim($contents);
    }
}
