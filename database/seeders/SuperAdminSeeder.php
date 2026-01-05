<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'superadmin@waba-biz.com')->exists()) return;

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@waba-biz.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'superadmin',
            'status' => 'offline',
            'is_active' => true,
        ]);
    }
}
