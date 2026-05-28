<?php

namespace Database\Factories;

use App\Enums\OpportunityStage;
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
            'stage' => OpportunityStage::Lead,
            'estimated_value' => fake()->randomFloat(2, 1000, 250000),
            'status' => null,
            'proposal_information' => null,
            'ai_recommendations' => null,
        ];
    }

    public function stage(OpportunityStage $stage): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => $stage,
        ]);
    }

    public function won(): static
    {
        return $this->stage(OpportunityStage::Won);
    }

    public function lost(): static
    {
        return $this->stage(OpportunityStage::Lost);
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
