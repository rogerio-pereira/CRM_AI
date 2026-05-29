<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class FollowUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::query()->limit(6)->get();

        if ($clients->isEmpty()) {
            return;
        }

        foreach ($clients as $client) {
            FollowUp::factory()
                ->count(2)
                ->for($client)
                ->create();

            $opportunity = Opportunity::query()->where('client_id', $client->id)->first();

            if ($opportunity !== null) {
                FollowUp::factory()
                    ->for($client)
                    ->for($opportunity)
                    ->create();
            }
        }

        FollowUp::factory()->overdue()->create([
            'client_id' => $clients->first()->id,
        ]);
    }
}
