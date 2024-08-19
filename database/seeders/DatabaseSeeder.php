<?php

namespace Database\Seeders;

use App\Models\IP;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'role' => 'admin',
            'name' => 'Administrator',
            'email' => 'administrator@example.com',
            'email_verified_at' => now(),
        ]);

        IP::factory()->create([
            'user_id' => 1,
            'ip_address' => '192.168.1.21',
            'status' => 1,
        ]);

        User::factory(20)->create();
        // Create 20 IP records with random user IDs from the predefined list
        for ($i=2; $i <=21 ; $i++) { 
            Ip::factory()->create([
                'user_id' => $i,
                'ip_address' => '192.168.1.'.rand(20,200),
            ]);
        }
    }
}
