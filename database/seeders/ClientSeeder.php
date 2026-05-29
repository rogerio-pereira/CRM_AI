<?php

namespace Database\Seeders;

use App\Models\Client;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
            $createdAt = $this->randomTimeOnDay(now()->subDays($daysAgo));
            $count = fake()->numberBetween(1, 3);

            Client::factory()
                ->count($count)
                ->createdAt($createdAt)
                ->create();
        }

        Client::factory()->archived()->createdAt($this->randomTimeOnDay(now()->subDays(12)))->create();
        Client::factory()->archived()->createdAt($this->randomTimeOnDay(now()->subDays(20)))->create();
        Client::factory()->ignored()->createdAt($this->randomTimeOnDay(now()->subDays(8)))->create();
        Client::factory()->ignored()->createdAt($this->randomTimeOnDay(now()->subDays(25)))->create();
        Client::factory()->contactIntent()->createdAt($this->randomTimeOnDay(now()->subDays(3)))->create();
        Client::factory()->contactIntent()->createdAt($this->randomTimeOnDay(now()->subDays(18)))->create();
    }

    private function randomTimeOnDay(CarbonInterface $day): Carbon
    {
        return Carbon::parse($day)
            ->startOfDay()
            ->addHours(fake()->numberBetween(8, 18))
            ->addMinutes(fake()->numberBetween(0, 59));
    }
}
