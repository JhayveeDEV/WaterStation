<?php

namespace Database\Seeders;

use App\Models\DeliveryArea;
use Illuminate\Database\Seeder;

class DeliveryAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Within 2 km (Barangay proper)', 'fee' => 0],
            ['name' => 'Within 5 km', 'fee' => 30],
            ['name' => 'Within 10 km', 'fee' => 50],
            ['name' => 'Outside city limits', 'fee' => 80],
        ];

        foreach ($areas as $area) {
            DeliveryArea::firstOrCreate(['name' => $area['name']], ['fee' => $area['fee'], 'is_active' => true]);
        }
    }
}
