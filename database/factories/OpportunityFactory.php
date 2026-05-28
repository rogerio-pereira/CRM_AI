<?php

namespace Database\Factories;

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
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => PipelineStage::Qualification,
        ]);
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => PipelineStage::Won,
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => PipelineStage::Lost,
        ]);
    }
}
