<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::factory()->count(12)->create();
        Client::factory()->archived()->count(2)->create();
        Client::factory()->ignored()->count(2)->create();
        Client::factory()->contactIntent()->count(2)->create();
    }
}
