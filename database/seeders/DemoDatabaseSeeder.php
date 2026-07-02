<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    /**
     * Demo seed.
     *
     * Use this only for testing or development databases.
     * Do not run this on the real production church database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DemoChurchSeeder::class,
        ]);
    }
}
