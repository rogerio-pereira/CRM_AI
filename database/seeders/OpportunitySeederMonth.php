<?php

namespace Database\Seeders;

use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OpportunitySeederMonth extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::query()->get();

        if ($clients->isEmpty()) {
            $clients = Client::factory()
                ->count(8)
                ->create();
        }

        for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
            $day = Carbon::now()
                           ->subDays($daysAgo);
            $createdAt = $this->randomTimeOnDay($day);
            $count = fake()->numberBetween(1, 2);

            Opportunity::factory()
                ->count($count)
                ->for($clients->random())
                ->createdAt($createdAt)
                ->create();
        }

        foreach (PipelineStage::ordered() as $stage) {
            $daysAgo = fake()->numberBetween(0, 29);
            $day = Carbon::now()
                           ->subDays($daysAgo);
            $createdAt = $this->randomTimeOnDay($day);

            Opportunity::factory()
                ->count(2)
                ->for($clients->random())
                ->stage($stage)
                ->createdAt($createdAt)
                ->create();
        }

        for ($daysAgo = 0; $daysAgo < 30; $daysAgo += 3) {
            $day = Carbon::now()
                           ->subDays($daysAgo);
            $wonAt = $this->randomTimeOnDay($day);

            Opportunity::factory()
                ->wonOn($wonAt)
                ->for($clients->random())
                ->create([
                    'estimated_value' => fake()->randomFloat(2, 2500, 75000),
                ]);
        }
    }

    private function randomTimeOnDay(CarbonInterface $day): Carbon
    {
        return Carbon::parse($day)
            ->startOfDay()
            ->addHours(fake()->numberBetween(8, 18))
            ->addMinutes(fake()->numberBetween(0, 59));
    }
}
