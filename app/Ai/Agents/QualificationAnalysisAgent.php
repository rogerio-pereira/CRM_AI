<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Providers\Tools\WebSearch;

#[MaxSteps(8)]
#[Timeout(120)]
class QualificationAnalysisAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        private readonly string $instructions,
    ) {}

    public function instructions(): string
    {
        return $this->instructions;
    }

    /**
     * @return iterable<int, WebSearch|WebFetch>
     */
    public function tools(): iterable
    {
        $webSearch = new WebSearch;
        $webSearch->location(
            city: 'Plant City',
            region: 'FL',
            country: 'US',
        );

        $webFetch = new WebFetch;

        return [
                $webSearch,
                $webFetch,
            ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $painPoint = $schema->object([
                'title' => $schema->string()
                                ->required(),
                'evidence' => $schema->string()
                                    ->required(),
                'business_impact' => $schema->string()
                                            ->required(),
            ]);

        $painPoints = $schema->array()
                            ->items($painPoint)
                            ->required();

        $opportunity = $schema->object([
                'service' => $schema->string()
                                    ->required(),
                'title' => $schema->string()
                                ->required(),
                'why_it_matters' => $schema->string()
                                            ->required(),
                'priority' => $schema->string()
                                    ->required(),
            ]);

        $opportunities = $schema->array()
                                ->items($opportunity)
                                ->required();

        $talkingPointItems = $schema->string();
        $talkingPoints = $schema->array()
                                ->items($talkingPointItems)
                                ->required();

        $avoidItems = $schema->string();
        $avoid = $schema->array()
                        ->items($avoidItems)
                        ->required();

        $contactExample = $schema->object([
                'channel' => $schema->string()
                                    ->required(),
                'subject' => $schema->string()
                                    ->required(),
                'body' => $schema->string()
                                ->required(),
            ]);

        $outreachStrategy = $schema->object([
                'positioning' => $schema->string()
                                        ->required(),
                'talking_points' => $talkingPoints,
                'contact_example' => $contactExample
                                        ->required(),
                'avoid' => $avoid,
            ]);

        $source = $schema->object([
                'label' => $schema->string()
                                ->required(),
                'url' => $schema->string()
                                ->required(),
                'observed_at' => $schema->string()
                                        ->required(),
            ]);

        $sources = $schema->array()
                        ->items($source)
                        ->required();

        $fit = $schema->object([
                'level' => $schema->string()
                                ->required(),
                'label' => $schema->string()
                                ->required(),
                'reason' => $schema->string()
                                ->required(),
            ]);

        $insights = $schema->object([
                'schema_version' => $schema->integer()
                                            ->required(),
                'generated_at' => $schema->string()
                                        ->required(),
                'source_agent' => $schema->string()
                                        ->required(),
                'language' => $schema->string()
                                    ->required(),
                'summary' => $schema->string()
                                    ->required(),
                'fit' => $fit->required(),
                'pain_points' => $painPoints,
                'opportunities' => $opportunities,
                'outreach_strategy' => $outreachStrategy
                                            ->required(),
                'sources' => $sources,
                'confidence' => $schema->string()
                                        ->required(),
            ]);

        return [
                'schema_version' => $schema->integer()
                                            ->required(),
                'agent' => $schema->string()
                                ->required(),
                'opportunity_id' => $schema->string()
                                            ->required(),
                'client_id' => $schema->string()
                                        ->required(),
                'qualification_status' => $schema->string()
                                                ->required(),
                'qualification_notes' => $schema->string()
                                                ->nullable(),
                'qualification_last_error' => $schema->string()
                                                    ->nullable(),
                'retry_recommended' => $schema->boolean()
                                            ->nullable(),
                'ai_insights' => $insights
                                    ->nullable(),
                'next_pipeline_stage' => $schema->string()
                                                ->nullable(),
            ];
    }
}
