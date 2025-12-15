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
        $this->call([
            SuperAdminSeeder::class,
            BroadcastDemoSeeder::class, // <–– tambahkan di sini
        ]);
<<<<<<< HEAD
        $this->call(WaMenuSeeder::class);
=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    }
}
