<?php

namespace App\Ai\Discovery;

use App\Ai\Contracts\DiscoveryAdapter;
use App\Support\UrlNormalizer;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class PublicWebDiscoveryAdapter implements DiscoveryAdapter
{
    private const DISCOVERY_PROMPT_PATH = 'docs/prompts/prospecting-discovery.md';

    /**
     * {@inheritdoc}
     */
    public function discover(array $options = []): array
    {
        $requestedLimit = $options['limit'] ?? 1;
        $limit = (int) $requestedLimit;

        if ($limit < 1) {
            $limit = 1;
        }

        $rawInstructions = $options['instructions'] ?? '';
        $instructions = trim((string) $rawInstructions);

        if ($instructions === '') {
            throw new RuntimeException('Prospecting discovery requires approved prompt instructions.');
        }

        $userPrompt = $this->loadDiscoveryPrompt();

        $excludeCompanyNames = $options['exclude_company_names'] ?? [];
        $totalExcluded = count($excludeCompanyNames);
        if ($totalExcluded > 0) {
            $list = implode(', ', $excludeCompanyNames);
            $userPrompt = $userPrompt."\n\nDo not return these companies already in the CRM: ".$list.'.';
        }

        $response = $this->promptDiscovery($instructions, $userPrompt);

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

            $skipped[] = $this->mapSkipped($item);
        }

        foreach ($rawLeads as $item) {
            if (! is_array($item)) {
                continue;
            }

            $mappedLead = $this->mapLead($item);

            if ($mappedLead === null) {
                $mappedSkipped = $this->mapSkipped($item);
                $mappedSkipped['reason'] = 'Missing company name or valid public email.';
                $skipped[] = $mappedSkipped;

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

    protected function promptDiscovery(string $instructions, string $userPrompt): AgentResponse
    {
        $agent = new ProspectingDiscoveryAgent($instructions);

        return $agent->prompt($userPrompt);
    }

    private function loadDiscoveryPrompt(): string
    {
        $path = base_path(self::DISCOVERY_PROMPT_PATH);

        if (! File::exists($path)) {
            throw new RuntimeException('Prospecting discovery prompt file not found: '.$path);
        }

        $contents = File::get($path);
        $prompt = trim((string) $contents);

        if ($prompt === '') {
            throw new RuntimeException('Prospecting discovery prompt file is empty: '.$path);
        }

        return $prompt;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function mapSkipped(array $item): array
    {
        $rawName = $item['name'] ?? '';
        $name = trim((string) $rawName);

        if ($name === '') {
            $rawCompanyName = $item['company_name'] ?? '';
            $name = trim((string) $rawCompanyName);
        }

        if ($name === '') {
            $name = 'Unknown';
        }

        $rawReason = $item['reason'] ?? '';
        $reason = (string) $rawReason;

        $rawWebsite = $item['website'] ?? null;
        $website = null;

        if (is_string($rawWebsite)) {
            $website = UrlNormalizer::normalize($rawWebsite);
        }

        $rawContactName = $item['contact_name'] ?? null;
        $contactName = null;

        if (is_string($rawContactName)) {
            $trimmedContactName = trim($rawContactName);

            if ($trimmedContactName !== '') {
                $contactName = $trimmedContactName;
            }
        }

        $rawEmail = $item['email'] ?? '';
        $trimmedEmail = trim((string) $rawEmail);
        $email = strtolower($trimmedEmail);
        $emailIsValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        if ($emailIsValid === false) {
            $email = null;
        }

        $rawPhone = $item['phone'] ?? null;
        $phone = null;

        if (is_string($rawPhone)) {
            $trimmedPhone = trim($rawPhone);

            if ($trimmedPhone !== '') {
                $phone = $trimmedPhone;
            }
        }

        return [
                'name' => $name,
                'reason' => $reason,
                'contact_name' => $contactName,
                'email' => $email,
                'phone' => $phone,
                'website' => $website,
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
