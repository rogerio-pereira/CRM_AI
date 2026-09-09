<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class OpportunityNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if ($user === null) {
            $user = User::factory()
                        ->create();
        }

        $opportunities = Opportunity::limit(6)
                            ->get();

        foreach ($opportunities as $opportunity) {
            OpportunityNote::factory()
                            ->count(2)
                            ->for($opportunity)
                            ->for($user)
                            ->create();
        }
    }
}
