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
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'social_links' => [
                [
                    'platform' => 'LinkedIn',
                    'url' => fake()->url(),
                ],
            ],
            'lead_source' => fake()->randomElement(['Website', 'Referral', 'Prospecting', 'Event']),
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
