<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\QualificationStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
            'qualification_status' => QualificationStatus::Pending,
            'qualification_last_error' => null,
            'qualified_at' => null,
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

    public function qualificationPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'qualification_status' => QualificationStatus::Pending,
            'qualification_last_error' => null,
            'qualified_at' => null,
        ]);
    }

    public function qualificationProcessing(): static
    {
        return $this->state(fn (array $attributes) => [
            'qualification_status' => QualificationStatus::Processing,
            'qualification_last_error' => null,
            'qualified_at' => null,
        ]);
    }

    public function qualificationQualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'qualification_status' => QualificationStatus::Qualified,
            'qualification_last_error' => null,
            'qualified_at' => now(),
            'ai_insights' => [
                'schema_version' => 1,
                'summary' => 'Ready for a first conversation.',
            ],
        ]);
    }

    public function qualificationFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'qualification_status' => QualificationStatus::Failed,
            'qualification_last_error' => 'Qualification could not be completed. The team can try again later.',
            'qualified_at' => null,
        ]);
    }

    public function createdAt(Carbon $at): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
