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
            'name'     => 'Administrator',
            'email'    => 'admin@waba-biz.com',
            'role'     => 'admin',
            'status'   => 'offline',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'approved_at' => now(),
        ]);
    }
}
