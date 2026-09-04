<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'rodu.pereira@gmail.com'],
            [
                'name' => 'Rogerio Pereira',
                'password' => '$2y$12$cmP2pvkQuYnz1SoXnnIYc.6b8HoJ/v510pm6HYRv./d8hg4rZ/oEy',
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'saahjessicaxp@gmail.com'],
            [
                'name' => 'Sarah Jessica Xavier Pereira',
                'password' => '$2y$12$7eC6j0PlU5GbRkYxgMZ2futB7ATNTadIxTT/us1P38PBpF0upn7O.',
                'email_verified_at' => now(),
            ]
        );
    }
}
