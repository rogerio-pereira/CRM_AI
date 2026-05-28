<?php

namespace Database\Seeders;

use App\Enums\PipelineStage;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::query()->limit(8)->get();

        if ($clients->isEmpty()) {
            $clients = Client::factory()->count(8)->create();
        }

        foreach (PipelineStage::ordered() as $stage) {
            Opportunity::factory()
                ->count(2)
                ->for($clients->random())
                ->stage($stage)
                ->create();
        }
    }
}
