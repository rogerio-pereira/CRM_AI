<?php

namespace Database\Seeders;

use App\Enums\OpportunityStage;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $clients = Client::query()->limit(5)->get();

        if ($clients->isEmpty()) {
            $clients = Client::factory()->count(5)->create();
        }

        foreach (OpportunityStage::ordered() as $stage) {
            Opportunity::factory()
                ->stage($stage)
                ->create([
                    'client_id' => $clients->random()->id,
                ]);
        }

        Opportunity::factory()->withAiRecommendations()->stage(OpportunityStage::Qualification)->create([
            'client_id' => $clients->first()->id,
        ]);
    }
}
