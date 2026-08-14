<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'opportunity_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Pending,
            'is_important' => false,
            'completed_at' => null,
        ];
    }

    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_important' => true,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(function (array $attributes): array {
            $dueAt = Carbon::now()
                        ->subDay();

            return [
                'due_at' => $dueAt,
                'status' => TaskStatus::Pending,
            ];
        });
    }

    public function done(): static
    {
        return $this->state(function (array $attributes): array {
            $completedAt = Carbon::now();

            return [
                'status' => TaskStatus::Done,
                'completed_at' => $completedAt,
            ];
        });
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
