<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\FirstContactEmailFailedException;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class FirstContactEmailAgent implements AiAgent
{
    public function __construct(
        private readonly OpportunityService $opportunities,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $rawOpportunityId = $context['opportunity_id'] ?? 0;
        $opportunityId = (int) $rawOpportunityId;
        $opportunity = Opportunity::with(['client', 'notes.user'])
                            ->findOrFail($opportunityId);
        $client = $opportunity->client;

        if ($client === null) {
            throw new RuntimeException('First contact email client not found for opportunity: '.$opportunity->id);
        }

        if ($opportunity->qualification_status !== QualificationStatus::Qualified) {
            return [
                    'agent' => 'first_contact_email',
                    'status' => 'skipped_not_qualified',
                    'opportunity_id' => $opportunity->id,
                    'client_id' => $client->id,
                ];
        }

        $opportunity->forgetContactExamples();
        $insights = $opportunity->ai_insights;
        $recommendations = $opportunity->ai_recommendations;

        if (
            ! is_array($insights) ||
            $insights === []
        ) {
            throw new FirstContactEmailFailedException('First contact email output was incomplete.');
        }

        $opportunity = $this->opportunities
                            ->update($opportunity, [
                                'ai_insights' => $insights,
                                'ai_recommendations' => $recommendations,
                            ]);

        $contactExample = $this->writeContactExample($client, $opportunity, $insights);
        $outreachStrategy = $insights['outreach_strategy'] ?? [];

        if (! is_array($outreachStrategy)) {
            $outreachStrategy = [];
        }

        $outreachStrategy['contact_example'] = $contactExample;
        $insights['outreach_strategy'] = $outreachStrategy;
        $updatedOpportunity = $this->opportunities
                                    ->update($opportunity, [
                                        'ai_insights' => $insights,
                                    ]);
        $trigger = $context['trigger'] ?? '';

        if ($trigger !== 'manual_email_refresh') {
            $updatedOpportunity = $this->moveToContactWhenReady($updatedOpportunity);
        }

        return [
                'agent' => 'first_contact_email',
                'status' => 'completed',
                'opportunity_id' => $updatedOpportunity->id,
                'client_id' => $client->id,
            ];
    }

    /**
     * @param  array<string, mixed>  $insights
     * @return array<string, mixed>
     */
    private function writeContactExample(Client $client, Opportunity $opportunity, array $insights): array
    {
        $dossier = $this->copywriterDossier($client, $opportunity, $insights);
        $encodedDossier = json_encode($dossier);

        if (! is_string($encodedDossier)) {
            throw new FirstContactEmailFailedException('First contact email output was incomplete.');
        }

        $copywriter = app(WriteFirstContactEmailAgent::class);
        $prompt = "Write the example first contact email for this lead. Return structured JSON only.\n\n".$encodedDossier;
        $response = $copywriter->prompt($prompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new FirstContactEmailFailedException('First contact email output was incomplete.');
        }

        $contactExample = $response->toArray();
        $rawSubject = $contactExample['subject'] ?? '';
        $subject = trim((string) $rawSubject);
        $rawBody = $contactExample['body'] ?? '';
        $body = trim((string) $rawBody);

        if (
            $subject === '' ||
            $body === ''
        ) {
            throw new FirstContactEmailFailedException('First contact email output was incomplete.');
        }

        return $contactExample;
    }

    /**
     * @param  array<string, mixed>  $insights
     * @return array<string, mixed>
     */
    private function copywriterDossier(Client $client, Opportunity $opportunity, array $insights): array
    {
        return [
                'client' => [
                    'id' => (string) $client->id,
                    'company_name' => $client->company_name,
                    'contact_name' => $client->contact_name,
                    'contact_email' => $client->contact_email,
                    'contact_phone' => $client->contact_phone,
                    'website' => $client->website,
                    'social_links' => $client->social_links,
                    'lead_source' => $client->lead_source,
                    'company_notes' => $client->qualification_notes,
                ],
                'opportunity' => [
                    'id' => (string) $opportunity->id,
                    'title' => $opportunity->title,
                    'stage' => $opportunity->stage->value,
                    'qualification_notes' => $opportunity->qualification_notes,
                ],
                'ai_insights' => $insights,
                'ai_recommendations' => $opportunity->ai_recommendations,
                'opportunity_notes' => $opportunity->notesForAiContext(),
            ];
    }

    private function moveToContactWhenReady(Opportunity $opportunity): Opportunity
    {
        if ($opportunity->stage !== PipelineStage::Qualification) {
            return $opportunity;
        }

        $movedOpportunity = $this->opportunities
                                ->moveToStage($opportunity, PipelineStage::Contact);

        return $movedOpportunity;
    }
}
