<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientAiInsight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAiInsight>
 */
class ClientAiInsightFactory extends Factory
{
    protected $model = ClientAiInsight::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'summary' => null,
        ];
    }
}
