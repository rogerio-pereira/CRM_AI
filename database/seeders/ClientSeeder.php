<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Client::factory()->count(6)->create();
        Client::factory()->archived()->count(2)->create();
        Client::factory()->ignored()->create();
        Client::factory()->contactIntent()->create();
    }
}
