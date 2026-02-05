<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'superadmin@auresys.id';
        if (User::where('email', $email)->exists()) return;

        User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make('Pakarul@123!'),
            'role' => 'superadmin',
            'status' => 'online',
            'is_active' => true,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
    }
}
