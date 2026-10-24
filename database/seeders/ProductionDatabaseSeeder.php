<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ProductionAdminSeeder::class,
            DeliveryAreaSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
