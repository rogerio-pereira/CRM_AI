<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[MaxSteps(6)]
#[Timeout(120)]
class RecommendationAnalysisAgent implements Agent, HasStructuredOutput
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

        $recommendedFocusItem = $schema->object([
                'service' => $schema->string()
                                    ->required(),
                'title' => $schema->string()
                                ->required(),
                'why_it_matters' => $schema->string()
                                            ->required(),
                'priority' => $schema->string()
                                    ->required(),
            ]);

        $recommendedFocus = $schema->array()
                                    ->items($recommendedFocusItem)
                                    ->required();

        $talkingPointItems = $schema->string();
        $talkingPoints = $schema->array()
                                ->items($talkingPointItems)
                                ->required();

        $questionItems = $schema->string();
        $questionsToAsk = $schema->array()
                                ->items($questionItems)
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

        $conversationStrategy = $schema->object([
                'positioning' => $schema->string()
                                        ->required(),
                'talking_points' => $talkingPoints,
                'contact_example' => $contactExample
                                        ->required(),
                'questions_to_ask' => $questionsToAsk,
                'avoid' => $avoid,
            ]);

        $nextStep = $schema->object([
                'title' => $schema->string()
                                ->required(),
                'reason' => $schema->string()
                                    ->required(),
            ]);

        $nextSteps = $schema->array()
                            ->items($nextStep)
                            ->required();

        $recommendations = $schema->object([
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
                'pain_points' => $painPoints,
                'recommended_focus' => $recommendedFocus,
                'conversation_strategy' => $conversationStrategy
                                                ->required(),
                'next_steps' => $nextSteps,
                'confidence' => $schema->string()
                                        ->required(),
            ]);

        return [
                'schema_version' => $schema->integer()
                                            ->required(),
                'agent' => $schema->string()
                                ->required(),
                'lead_id' => $schema->string()
                                    ->required(),
                'opportunity_id' => $schema->string()
                                            ->required(),
                'ai_recommendations' => $recommendations
                                            ->required(),
            ];
    }
}
