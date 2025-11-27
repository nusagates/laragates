<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@waba-biz.com')->exists()) return;

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@waba-biz.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
            'status' => 'offline',
            'is_active' => true,
        ]);
    }
}
