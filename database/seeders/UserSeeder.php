<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@auresys.id',
            'role' => 'admin',
            'status' => 'offline',
            'email_verified_at' => now(),
            'password' => Hash::make('Xuxoken@32'),
            'approved_at' => now(),
        ]);

        // agent sample
        User::create([
            'name' => 'Agent One',
            'email' => 'agent1@auresys.id',
            'role' => 'agent',
            'status' => 'offline',
            'email_verified_at' => now(),
            'password' => Hash::make('Xuxoken@32'),
            'approved_at' => now(),
            'skills' => ['sales', 'support'],
        ]);

        User::create([
            'name' => 'Agent Two',
            'email' => 'agent2@auresys.id',
            'role' => 'agent',
            'status' => 'offline',
            'email_verified_at' => now(),
            'password' => Hash::make('Xuxoken@32'),
            'approved_at' => now(),
            'skills' => ['support'],
        ]);
    }
}
