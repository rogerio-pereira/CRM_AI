<?php

namespace Database\Factories;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'title' => fake()->sentence(3),
            'stage' => PipelineStage::Lead,
            'estimated_value' => fake()->randomFloat(2, 1000, 50000),
            'status' => OpportunityStatus::Open,
            'proposal_notes' => null,
            'proposal_payload' => null,
            'ai_recommendations' => null,
        ];
    }

    public function stage(PipelineStage $stage): static
    {
        return $this->state(function (array $attributes) use ($stage): array {
            $status = OpportunityStatus::Open;

            if ($stage === PipelineStage::Won) {
                $status = OpportunityStatus::Won;
            }

            if ($stage === PipelineStage::Lost) {
                $status = OpportunityStatus::Lost;
            }

            return [
                'stage' => $stage,
                'status' => $status,
            ];
        });
    }

    public function open(): static
    {
        return $this->stage(PipelineStage::Qualification);
    }

    public function won(): static
    {
        return $this->stage(PipelineStage::Won);
    }

    public function lost(): static
    {
        return $this->stage(PipelineStage::Lost);
    }

    public function withAiRecommendations(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_recommendations' => [
                'summary' => fake()->sentence(),
            ],
        ]);
    }
}
