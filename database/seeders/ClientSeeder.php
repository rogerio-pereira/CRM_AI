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
            $day = Carbon::now()
                           ->subDays($daysAgo);
            $createdAt = $this->randomTimeOnDay($day);
            $count = fake()->numberBetween(1, 3);

            Client::factory()
                ->count($count)
                ->createdAt($createdAt)
                ->create();
        }

        $archivedDayA = Carbon::now()
                                ->subDays(12);
        $archivedCreatedAtA = $this->randomTimeOnDay($archivedDayA);
        Client::factory()
            ->archived()
            ->createdAt($archivedCreatedAtA)
            ->create();

        $archivedDayB = Carbon::now()
                                ->subDays(20);
        $archivedCreatedAtB = $this->randomTimeOnDay($archivedDayB);
        Client::factory()
            ->archived()
            ->createdAt($archivedCreatedAtB)
            ->create();

        $ignoredDayA = Carbon::now()
                               ->subDays(8);
        $ignoredCreatedAtA = $this->randomTimeOnDay($ignoredDayA);
        Client::factory()
            ->ignored()
            ->createdAt($ignoredCreatedAtA)
            ->create();

        $ignoredDayB = Carbon::now()
                               ->subDays(25);
        $ignoredCreatedAtB = $this->randomTimeOnDay($ignoredDayB);
        Client::factory()
            ->ignored()
            ->createdAt($ignoredCreatedAtB)
            ->create();

        $contactIntentDayA = Carbon::now()
                                     ->subDays(3);
        $contactIntentCreatedAtA = $this->randomTimeOnDay($contactIntentDayA);
        Client::factory()
            ->contactIntent()
            ->createdAt($contactIntentCreatedAtA)
            ->create();

        $contactIntentDayB = Carbon::now()
                                     ->subDays(18);
        $contactIntentCreatedAtB = $this->randomTimeOnDay($contactIntentDayB);
        Client::factory()
            ->contactIntent()
            ->createdAt($contactIntentCreatedAtB)
            ->create();
    }

    private function randomTimeOnDay(CarbonInterface $day): Carbon
    {
        return Carbon::parse($day)
            ->startOfDay()
            ->addHours(fake()->numberBetween(8, 18))
            ->addMinutes(fake()->numberBetween(0, 59));
    }
}
