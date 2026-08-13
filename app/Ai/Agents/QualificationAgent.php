<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\QualificationFailedException;
use App\Enums\AgentType;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Services\AiOrchestrationService;
use App\Services\ClientService;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class QualificationAgent implements AiAgent
{
    private const APPROVED_PROMPT_PATH = 'docs/prompts/qualification-agent.md';

    public function __construct(
        private readonly ClientService $clients,
        private readonly OpportunityService $opportunities,
        private readonly AiOrchestrationService $orchestration,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $client = $this->loadClient($context);

        if ($client->qualification_status === QualificationStatus::Qualified) {
            $this->advanceLinkedOpportunitiesToContact($client);

            return [
                    'agent' => 'qualification',
                    'status' => 'already_qualified',
                    'client_id' => $client->id,
                ];
        }

        $this->markProcessing($client);
        $this->advanceLinkedOpportunitiesFromLeadToQualification($client);

        $payload = $this->analyzeLead($client);
        $this->assertSuccessfulQualification($payload);
        $this->persistSuccessfulQualification($client, $payload);
        $this->advanceLinkedOpportunitiesToContact($client);
        $this->dispatchRecommendation($client);

        return [
                'agent' => 'qualification',
                'status' => 'qualified',
                'client_id' => $client->id,
            ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function loadClient(array $context): Client
    {
        $rawClientId = $context['client_id'] ?? null;

        if ($rawClientId === null) {
            throw new RuntimeException('Qualification requires a client_id.');
        }

        $clientId = (int) $rawClientId;
        $client = Client::find($clientId);

        if ($client === null) {
            throw new RuntimeException('Qualification client not found: '.$clientId);
        }

        return $client;
    }

    private function markProcessing(Client $client): void
    {
        $this->clients
                ->update($client, [
                    'qualification_status' => QualificationStatus::Processing,
                    'qualification_last_error' => null,
                ]);
    }

    private function advanceLinkedOpportunitiesFromLeadToQualification(Client $client): void
    {
        $opportunities = $client->opportunities()
                                ->where('stage', PipelineStage::Lead->value)
                                ->get();

        foreach ($opportunities as $opportunity) {
            $this->opportunities
                    ->moveToStage($opportunity, PipelineStage::Qualification);
        }
    }

    private function advanceLinkedOpportunitiesToContact(Client $client): void
    {
        $opportunities = $client->opportunities()
                                ->whereIn('stage', [
                                    PipelineStage::Lead->value,
                                    PipelineStage::Qualification->value,
                                ])
                                ->get();

        foreach ($opportunities as $opportunity) {
            $this->opportunities
                    ->moveToStage($opportunity, PipelineStage::Contact);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeLead(Client $client): array
    {
        $instructions = $this->loadApprovedPrompt();
        $userPrompt = $this->buildUserPrompt($client);
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
                $error = 'The lead could not be qualified from the available information.';
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
    private function persistSuccessfulQualification(Client $client, array $payload): void
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

        $this->clients
                ->update($client, [
                    'qualification_notes' => $notes,
                    'qualification_status' => QualificationStatus::Qualified,
                    'qualification_last_error' => null,
                    'qualified_at' => now(),
                    'ai_insights' => $insights,
                ]);
    }

    private function dispatchRecommendation(Client $client): void
    {
        $this->orchestration
                ->dispatch(AgentType::Recommendation, [
                    'trigger' => 'qualification_completed',
                    'client_id' => $client->id,
                ]);
    }

    private function buildUserPrompt(Client $client): string
    {
        $leadPayload = [
                'lead_id' => (string) $client->id,
                'company_name' => $client->company_name,
                'contact_name' => $client->contact_name,
                'contact_email' => $client->contact_email,
                'contact_phone' => $client->contact_phone,
                'website' => $client->website,
                'social_links' => $client->social_links,
                'lead_source' => $client->lead_source,
                'qualification_notes' => $client->qualification_notes,
            ];

        $encodedLead = json_encode($leadPayload);

        if (! is_string($encodedLead)) {
            throw new RuntimeException('Qualification lead payload could not be encoded.');
        }

        return "Qualify this lead. Return structured JSON only.\n\n".$encodedLead;
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
