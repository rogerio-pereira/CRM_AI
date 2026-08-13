<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\QualificationFailedException;
use App\Enums\AgentType;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\AiOrchestrationService;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class QualificationAgent implements AiAgent
{
    private const APPROVED_PROMPT_PATH = 'docs/prompts/qualification-agent.md';

    private const SERVICE_CATALOG_DIRECTORY = 'docs/services';

    public function __construct(
        private readonly OpportunityService $opportunities,
        private readonly AiOrchestrationService $orchestration,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $opportunity = $this->loadOpportunity($context);
        $client = $this->loadClient($opportunity);

        if ($opportunity->qualification_status === QualificationStatus::Qualified) {
            return [
                    'agent' => 'qualification',
                    'status' => 'already_qualified',
                    'opportunity_id' => $opportunity->id,
                    'client_id' => $client->id,
                ];
        }

        $this->markProcessing($opportunity);
        $this->advanceThisOpportunityFromLeadToQualification($opportunity);

        $freshOpportunity = $opportunity->fresh(['client']);

        if ($freshOpportunity === null) {
            throw new RuntimeException('Qualification opportunity not found: '.$opportunity->id);
        }

        $payload = $this->analyzeOpportunity($freshOpportunity, $client);
        $this->assertSuccessfulQualification($payload);
        $this->persistSuccessfulQualification($freshOpportunity, $payload);
        $this->advanceThisOpportunityToContact($freshOpportunity);
        $this->dispatchRecommendation($freshOpportunity, $client);

        return [
                'agent' => 'qualification',
                'status' => 'qualified',
                'opportunity_id' => $opportunity->id,
                'client_id' => $client->id,
            ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function loadOpportunity(array $context): Opportunity
    {
        $rawOpportunityId = $context['opportunity_id'] ?? null;

        if ($rawOpportunityId === null) {
            throw new RuntimeException('Qualification requires an opportunity_id.');
        }

        $opportunityId = (int) $rawOpportunityId;
        $opportunity = Opportunity::find($opportunityId);

        if ($opportunity === null) {
            throw new RuntimeException('Qualification opportunity not found: '.$opportunityId);
        }

        return $opportunity;
    }

    private function loadClient(Opportunity $opportunity): Client
    {
        $client = $opportunity->client;

        if ($client === null) {
            throw new RuntimeException('Qualification client not found for opportunity: '.$opportunity->id);
        }

        return $client;
    }

    private function markProcessing(Opportunity $opportunity): void
    {
        $this->opportunities
                ->update($opportunity, [
                    'qualification_status' => QualificationStatus::Processing,
                    'qualification_last_error' => null,
                ]);
    }

    private function advanceThisOpportunityFromLeadToQualification(Opportunity $opportunity): void
    {
        $freshOpportunity = $opportunity->fresh();

        if ($freshOpportunity === null) {
            return;
        }

        if ($freshOpportunity->stage !== PipelineStage::Lead) {
            return;
        }

        $this->opportunities
                ->moveToStage($freshOpportunity, PipelineStage::Qualification);
    }

    private function advanceThisOpportunityToContact(Opportunity $opportunity): void
    {
        $freshOpportunity = $opportunity->fresh();

        if ($freshOpportunity === null) {
            return;
        }

        $this->opportunities
                ->moveToStage($freshOpportunity, PipelineStage::Contact);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeOpportunity(Opportunity $opportunity, Client $client): array
    {
        $instructions = $this->loadApprovedPrompt();
        $userPrompt = $this->buildUserPrompt($opportunity, $client);
        $response = $this->promptAnalysis($instructions, $userPrompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        return $response->toArray();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSuccessfulQualification(array $payload): void
    {
        $rawStatus = $payload['qualification_status'] ?? '';
        $status = strtolower(trim((string) $rawStatus));

        if ($status === 'failed') {
            $rawError = $payload['qualification_last_error'] ?? '';
            $error = trim((string) $rawError);

            if ($error === '') {
                $error = 'The opportunity could not be qualified from the available information.';
            }

            throw new QualificationFailedException($error);
        }

        if ($status !== 'qualified') {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        $insights = $payload['ai_insights'] ?? null;

        if (! is_array($insights)) {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        $outreachStrategy = $insights['outreach_strategy'] ?? null;

        if (! is_array($outreachStrategy)) {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        $contactExample = $outreachStrategy['contact_example'] ?? null;

        if (! is_array($contactExample)) {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        $rawSubject = $contactExample['subject'] ?? '';
        $subject = trim((string) $rawSubject);
        $rawBody = $contactExample['body'] ?? '';
        $body = trim((string) $rawBody);

        if ($subject === '') {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        if ($body === '') {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistSuccessfulQualification(Opportunity $opportunity, array $payload): void
    {
        $rawNotes = $payload['qualification_notes'] ?? null;
        $notes = null;

        if (is_string($rawNotes)) {
            $notes = trim($rawNotes);
        }

        if ($notes === '') {
            $notes = null;
        }

        $insights = $payload['ai_insights'] ?? null;

        if (! is_array($insights)) {
            throw new QualificationFailedException('Qualification output was incomplete.');
        }

        $rawGeneratedAt = $insights['generated_at'] ?? null;
        $hasGeneratedAt = is_string($rawGeneratedAt) && $rawGeneratedAt !== '';

        if ($hasGeneratedAt === false) {
            $insights['generated_at'] = now()->toIso8601String();
        }

        $rawSourceAgent = $insights['source_agent'] ?? null;
        $hasSourceAgent = is_string($rawSourceAgent) && $rawSourceAgent !== '';

        if ($hasSourceAgent === false) {
            $insights['source_agent'] = 'qualification';
        }

        $schemaVersion = $insights['schema_version'] ?? null;

        if ($schemaVersion === null) {
            $insights['schema_version'] = 1;
        }

        $this->opportunities
                ->update($opportunity, [
                    'qualification_notes' => $notes,
                    'qualification_status' => QualificationStatus::Qualified,
                    'qualification_last_error' => null,
                    'qualified_at' => now(),
                    'ai_insights' => $insights,
                ]);
    }

    private function dispatchRecommendation(Opportunity $opportunity, Client $client): void
    {
        $this->orchestration
                ->dispatch(AgentType::Recommendation, [
                    'trigger' => 'qualification_completed',
                    'opportunity_id' => $opportunity->id,
                    'client_id' => $client->id,
                ]);
    }

    private function buildUserPrompt(Opportunity $opportunity, Client $client): string
    {
        $mode = 'later_opportunity';
        $isInitialProspecting = $this->isInitialProspectingQualification($opportunity, $client);

        if ($isInitialProspecting) {
            $mode = 'initial_prospecting';
        }

        $catalog = $this->loadServiceCatalog();
        $opportunityPayload = [
                'mode' => $mode,
                'opportunity_id' => (string) $opportunity->id,
                'client_id' => (string) $client->id,
                'company_name' => $client->company_name,
                'contact_name' => $client->contact_name,
                'contact_email' => $client->contact_email,
                'contact_phone' => $client->contact_phone,
                'website' => $client->website,
                'social_links' => $client->social_links,
                'lead_source' => $client->lead_source,
                'company_notes' => $client->qualification_notes,
                'opportunity_title' => $opportunity->title,
                'opportunity_stage' => $opportunity->stage->value,
                'service_catalog' => $catalog,
            ];

        $encodedPayload = json_encode($opportunityPayload);

        if (! is_string($encodedPayload)) {
            throw new RuntimeException('Qualification opportunity payload could not be encoded.');
        }

        if ($isInitialProspecting) {
            return "Qualify this opportunity in initial prospecting mode. Score every service in the catalog. Return structured JSON only.\n\n".$encodedPayload;
        }

        return "Qualify this later opportunity as this deal only. Return structured JSON only.\n\n".$encodedPayload;
    }

    private function isInitialProspectingQualification(Opportunity $opportunity, Client $client): bool
    {
        $leadSource = strtolower(trim((string) $client->lead_source));

        if ($leadSource !== 'prospecting') {
            return false;
        }

        $otherOpportunitiesExist = Opportunity::where('client_id', $client->id)
                                        ->where('id', '!=', $opportunity->id)
                                        ->exists();

        if ($otherOpportunitiesExist) {
            return false;
        }

        return true;
    }

    /**
     * @return list<array{file: string, contents: string}>
     */
    private function loadServiceCatalog(): array
    {
        $directory = base_path(self::SERVICE_CATALOG_DIRECTORY);
        $files = File::files($directory);
        $catalog = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $isMarkdown = str_ends_with($filename, '.md');

            if ($isMarkdown === false) {
                continue;
            }

            $pathname = $file->getPathname();
            $contents = File::get($pathname);
            $trimmed = trim((string) $contents);

            $catalog[] = [
                    'file' => $filename,
                    'contents' => $trimmed,
                ];
        }

        usort($catalog, function (array $left, array $right): int {
            return strcmp($left['file'], $right['file']);
        });

        return $catalog;
    }

    private function promptAnalysis(string $instructions, string $userPrompt): AgentResponse
    {
        $agent = new QualificationAnalysisAgent($instructions);

        return $agent->prompt($userPrompt);
    }

    private function loadApprovedPrompt(): string
    {
        $path = base_path(self::APPROVED_PROMPT_PATH);

        if (! File::exists($path)) {
            throw new RuntimeException('Qualification prompt file not found: '.$path);
        }

        $contents = File::get($path);
        $prompt = trim((string) $contents);

        if ($prompt === '') {
            throw new RuntimeException('Qualification prompt file is empty: '.$path);
        }

        return $prompt;
    }
}
