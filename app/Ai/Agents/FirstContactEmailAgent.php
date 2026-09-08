<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\FirstContactEmailFailedException;
use App\Ai\Tools\WriteFirstContactEmail;
use App\Enums\AgentType;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Services\AiOrchestrationService;
use App\Services\OpportunityService;
use Laravel\Ai\Tools\Request;
use RuntimeException;

class FirstContactEmailAgent implements AiAgent
{
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

        $insights = $opportunity->ai_insights;

        if (! is_array($insights)) {
            throw new FirstContactEmailFailedException('First contact email output was incomplete.');
        }

        $contactExample = $this->writeContactExample($client, $insights);
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
        $updatedOpportunity = $this->moveToContactWhenReady($updatedOpportunity);
        $this->orchestration
                ->dispatch(AgentType::Recommendation, [
                    'trigger' => 'first_contact_email_completed',
                    'opportunity_id' => $updatedOpportunity->id,
                    'client_id' => $client->id,
                ]);

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
    private function writeContactExample(Client $client, array $insights): array
    {
        $brief = $this->firstContactBrief($client, $insights);
        $tool = app(WriteFirstContactEmail::class);
        $request = new Request($brief);
        $encoded = $tool->handle($request);
        $contactExample = json_decode((string) $encoded, true);

        if (! is_array($contactExample)) {
            throw new FirstContactEmailFailedException('First contact email output was incomplete.');
        }

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
     * @return array<string, string>
     */
    private function firstContactBrief(Client $client, array $insights): array
    {
        $contactName = (string) ($client->contact_name ?? '');

        if ($contactName === '') {
            $contactName = (string) $client->company_name;
        }

        $firstPain = $this->firstArrayItem($insights['pain_points'] ?? []);
        $preferredOpportunity = $this->preferredCatalogItem($insights['opportunities'] ?? []);
        $rawHook = $firstPain['evidence'] ?? $firstPain['title'] ?? '';
        $rawService = $preferredOpportunity['service'] ?? 'lead_generation';
        $rawOpportunity = $preferredOpportunity['why_it_matters'] ?? $preferredOpportunity['title'] ?? '';
        $rawSummary = $insights['summary'] ?? '';

        return [
                'contact_name' => $contactName,
                'company_name' => (string) $client->company_name,
                'line_of_business' => (string) $rawSummary,
                'location' => 'Plant City, FL area',
                'service_angle' => (string) $rawService,
                'observed_hook' => (string) $rawHook,
                'opportunity' => (string) $rawOpportunity,
                'sample_insight' => (string) $rawSummary,
            ];
    }

    /**
     * @return array<string, mixed>
     */
    private function preferredCatalogItem(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $priorityOrder = [
                'high',
                'medium',
                'low',
            ];

        foreach ($priorityOrder as $priority) {
            $match = $this->firstItemWithPriority($items, $priority);

            if ($match !== []) {
                return $match;
            }
        }

        return $this->firstArrayItem($items);
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<string, mixed>
     */
    private function firstItemWithPriority(array $items, string $priority): array
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemPriority = $item['priority'] ?? '';

            if ($itemPriority === $priority) {
                return $item;
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function firstArrayItem(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $firstItem = $items[0] ?? [];

        if (! is_array($firstItem)) {
            return [];
        }

        return $firstItem;
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
