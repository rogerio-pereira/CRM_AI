<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\RecommendationFailedException;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class RecommendationAgent implements AiAgent
{
    private const APPROVED_PROMPT_PATH = 'docs/prompts/recommendation-agent.md';

    public function __construct(
        private readonly OpportunityService $opportunities,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $opportunityId = $context['opportunity_id'];
        $opportunity = Opportunity::with('client')
                            ->findOrFail($opportunityId);
        $client = $opportunity->client;

        if ($client === null) {
            throw new RuntimeException('Recommendation client not found for opportunity: '.$opportunity->id);
        }

        if ($opportunity->qualification_status !== QualificationStatus::Qualified) {
            return [
                    'agent' => 'recommendation',
                    'status' => 'skipped_not_qualified',
                    'opportunity_id' => $opportunity->id,
                    'client_id' => $client->id,
                ];
        }

        $payload = $this->analyzeOpportunity($opportunity, $client);
        $this->assertSuccessfulRecommendation($payload);
        $updatedOpportunity = $this->persistRecommendations($opportunity, $payload);
        $this->moveToContactWhenReady($updatedOpportunity);

        return [
                'agent' => 'recommendation',
                'status' => 'completed',
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
        $agent = app(RecommendationAnalysisAgent::class, [
                'instructions' => $instructions,
            ]);
        $response = $agent->prompt($userPrompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new RecommendationFailedException('Recommendation output was incomplete.');
        }

        $payload = $response->toArray();

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSuccessfulRecommendation(array $payload): void
    {
        $recommendations = $payload['ai_recommendations'] ?? [];
        $summary = $recommendations['summary'] ?? '';
        $conversationStrategy = $recommendations['conversation_strategy'] ?? [];
        $contactExample = $conversationStrategy['contact_example'] ?? [];
        $subject = $contactExample['subject'] ?? '';
        $body = $contactExample['body'] ?? '';

        if (
            $summary === '' ||
            $subject === '' ||
            $body === ''
        ) {
            $payloadKeys = array_keys($payload);

            Log::warning('ai.recommendation.incomplete', [
                'has_recommendations' => $recommendations !== [],
                'summary_length' => strlen($summary),
                'subject_length' => strlen($subject),
                'body_length' => strlen($body),
                'payload_keys' => $payloadKeys,
            ]);

            throw new RecommendationFailedException('Recommendation output was incomplete.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistRecommendations(Opportunity $opportunity, array $payload): Opportunity
    {
        $rawRecommendations = $payload['ai_recommendations'] ?? null;

        if (! is_array($rawRecommendations)) {
            throw new RecommendationFailedException('Recommendation output was incomplete.');
        }

        $now = now()->toIso8601String();
        $generatedAt = $rawRecommendations['generated_at'] ?? $now;
        $language = $rawRecommendations['language'] ?? 'en';
        $confidence = $rawRecommendations['confidence'] ?? 'medium';
        $painPoints = $rawRecommendations['pain_points'] ?? [];
        $recommendedFocus = $rawRecommendations['recommended_focus'] ?? [];
        $conversationStrategy = $rawRecommendations['conversation_strategy'] ?? [];
        $nextSteps = $rawRecommendations['next_steps'] ?? [];

        $recommendations = [
                'schema_version' => 1,
                'generated_at' => $generatedAt,
                'source_agent' => 'recommendation',
                'language' => $language,
                'summary' => $rawRecommendations['summary'] ?? '',
                'pain_points' => $painPoints,
                'opportunities' => $recommendedFocus,
                'outreach_strategy' => $conversationStrategy,
                'next_steps' => $nextSteps,
                'confidence' => $confidence,
            ];

        $updatedOpportunity = $this->opportunities
                                    ->update($opportunity, [
                                        'ai_recommendations' => $recommendations,
                                    ]);

        return $updatedOpportunity;
    }

    private function moveToContactWhenReady(Opportunity $opportunity): void
    {
        if ($opportunity->stage !== PipelineStage::Qualification) {
            return;
        }

        $this->opportunities
                ->moveToStage($opportunity, PipelineStage::Contact);
    }

    private function buildUserPrompt(Opportunity $opportunity, Client $client): string
    {
        $recommendationPayload = [
                'opportunity_id' => (string) $opportunity->id,
                'lead_id' => (string) $client->id,
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
                'qualification_notes' => $opportunity->qualification_notes,
                'ai_insights' => $opportunity->ai_insights,
            ];

        $encodedPayload = json_encode($recommendationPayload);

        if (! is_string($encodedPayload)) {
            throw new RuntimeException('Recommendation opportunity payload could not be encoded.');
        }

        return "Generate internal recommendations for this qualified opportunity. Return structured JSON only.\n\n".$encodedPayload;
    }

    private function loadApprovedPrompt(): string
    {
        $path = base_path(self::APPROVED_PROMPT_PATH);

        if (! File::exists($path)) {
            throw new RuntimeException('Recommendation prompt file not found: '.$path);
        }

        $contents = File::get($path);
        $prompt = trim((string) $contents);

        if ($prompt === '') {
            throw new RuntimeException('Recommendation prompt file is empty: '.$path);
        }

        return $prompt;
    }
}
