<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::query()->limit(5)->get();

        if ($clients->isEmpty()) {
            return;
        }

        foreach ($clients as $client) {
            Task::factory()
                ->count(2)
                ->for($client)
                ->create();

            Task::factory()
                ->important()
                ->for($client)
                ->create();
        }
    }
}
