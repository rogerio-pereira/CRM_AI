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
        $rawOpportunityId = $context['opportunity_id'] ?? 0;
        $opportunityId = (int) $rawOpportunityId;
        $opportunity = Opportunity::with('client')
                            ->findOrFail($opportunityId);
        $client = $opportunity->client;

        if ($client === null) {
            throw new RuntimeException('Qualification client not found for opportunity: '.$opportunity->id);
        }

        if ($opportunity->qualification_status === QualificationStatus::Qualified) {
            return [
                    'agent' => 'qualification',
                    'status' => 'already_qualified',
                    'opportunity_id' => $opportunity->id,
                    'client_id' => $client->id,
                ];
        }

        $this->opportunities
                ->update($opportunity, [
                    'qualification_status' => QualificationStatus::Processing,
                    'qualification_last_error' => null,
                ]);

        if ($opportunity->stage === PipelineStage::Lead) {
            $this->opportunities
                    ->moveToStage($opportunity, PipelineStage::Qualification);
        }

        $payload = $this->analyzeOpportunity($opportunity, $client);
        $this->assertSuccessfulQualification($payload);
        $this->persistSuccessfulQualification($opportunity, $payload);
        $this->opportunities
                ->moveToStage($opportunity, PipelineStage::Contact);
        $this->orchestration
                ->dispatch(AgentType::Recommendation, [
                    'trigger' => 'qualification_completed',
                    'opportunity_id' => $opportunity->id,
                    'client_id' => $client->id,
                ]);

        return [
                'agent' => 'qualification',
                'status' => 'qualified',
                'opportunity_id' => $opportunity->id,
                'client_id' => $client->id,
            ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeOpportunity(Opportunity $opportunity, Client $client): array
    {
        $instructions = $this->loadApprovedPrompt();
        $userPrompt = $this->buildUserPrompt($opportunity, $client);
        $agentParameters = [
                'instructions' => $instructions,
            ];
        $agent = app(QualificationAnalysisAgent::class, $agentParameters);
        $response = $agent->prompt($userPrompt);

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
        $rawStatus = (string) ($payload['qualification_status'] ?? '');
        $trimmedStatus = trim($rawStatus);
        $status = strtolower($trimmedStatus);

        if ($status === 'failed') {
            $rawError = $payload['qualification_last_error'] ?? '';
            $error = trim((string) $rawError);

            if ($error === '') {
                $error = 'The opportunity could not be qualified from the available information.';
            }

            throw new QualificationFailedException($error);
        }

        $insights = $payload['ai_insights'] ?? null;
        $outreachStrategy = null;
        $contactExample = null;
        $subject = '';
        $body = '';

        if (is_array($insights)) {
            $outreachStrategy = $insights['outreach_strategy'] ?? null;
        }

        if (is_array($outreachStrategy)) {
            $contactExample = $outreachStrategy['contact_example'] ?? null;
        }

        if (is_array($contactExample)) {
            $rawSubject = $contactExample['subject'] ?? '';
            $subject = trim((string) $rawSubject);
            $rawBody = $contactExample['body'] ?? '';
            $body = trim((string) $rawBody);
        }

        if (
            $status !== 'qualified' ||
            $subject === '' ||
            $body === ''
        ) {
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

        $this->opportunities
                ->update($opportunity, [
                    'qualification_notes' => $notes,
                    'qualification_status' => QualificationStatus::Qualified,
                    'qualification_last_error' => null,
                    'qualified_at' => now(),
                    'ai_insights' => $insights,
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
        $rawLeadSource = (string) $client->lead_source;
        $trimmedLeadSource = trim($rawLeadSource);
        $leadSource = strtolower($trimmedLeadSource);

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

            $catalog[$filename] = [
                    'file' => $filename,
                    'contents' => $trimmed,
                ];
        }

        ksort($catalog);
        $sortedCatalog = array_values($catalog);

        return $sortedCatalog;
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
