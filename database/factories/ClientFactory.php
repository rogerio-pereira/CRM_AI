<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'contacts' => [
                [
                    'name' => fake()->name(),
                    'email' => fake()->companyEmail(),
                    'phone' => fake()->phoneNumber(),
                ],
            ],
            'website' => fake()->optional()->url(),
            'social_links' => [
                'linkedin' => fake()->optional()->url(),
            ],
            'lead_source' => fake()->optional()->randomElement([
                'Website',
                'Referral',
                'Prospecting',
                'Event',
            ]),
            'qualification_notes' => fake()->optional()->paragraph(),
            'ai_insights' => null,
            'status' => ClientStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::Archived,
        ]);
    }

    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::Ignored,
        ]);
    }

    public function contactIntent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::ContactIntent,
        ]);
    }
}
