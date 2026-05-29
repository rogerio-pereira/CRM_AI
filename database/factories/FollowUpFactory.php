<?php

namespace Database\Factories;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpReminderStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'opportunity_id' => null,
            'due_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'priority' => FollowUpPriority::Medium,
            'notes' => fake()->optional()->sentence(),
            'reminder_status' => FollowUpReminderStatus::Pending,
            'completed_at' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDay(),
            'reminder_status' => FollowUpReminderStatus::Pending,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'reminder_status' => FollowUpReminderStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function withOpportunity(): static
    {
        return $this->state(function (array $attributes): array {
            $clientId = $attributes['client_id'] ?? Client::factory();

            return [
                'client_id' => $clientId,
                'opportunity_id' => Opportunity::factory()->for(
                    is_int($clientId) ? Client::find($clientId) : $clientId
                ),
            ];
        });
    }
}
