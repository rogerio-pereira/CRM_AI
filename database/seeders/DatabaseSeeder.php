<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * =============================================================================================================
         * REAL DATA
         * =============================================================================================================
         */
        $this->call([
            UserSeeder::class,
        ]);

        /*
         * =============================================================================================================
         * FAKE DATA
         * =============================================================================================================
         */
        $currentEnv = config('app.env');
        if (
            $currentEnv === 'local' ||
            $currentEnv === 'testing'
        ) {
            $this->call([
                ClientSeeder::class,
                OpportunitySeeder::class,
                OpportunitySeederMonth::class,
                OpportunityNoteSeeder::class,
                FollowUpSeeder::class,
                TaskSeeder::class,
            ]);
        }
    }
}
