<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/** Catalog is intentionally gallon-water only — see StoreProductRequest for the enforced container types. */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Round Gallon Refill', 'container_type' => 'Round Gallon', 'price' => 30, 'stock' => 120],
            ['name' => 'Slim Gallon Refill', 'container_type' => 'Slim Gallon', 'price' => 30, 'stock' => 100],
            ['name' => 'New Round Gallon (with water)', 'container_type' => 'Round Gallon', 'price' => 150, 'stock' => 25],
            ['name' => 'New Slim Gallon (with water)', 'container_type' => 'Slim Gallon', 'price' => 150, 'stock' => 25],
        ];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => 'Purified drinking water, ready for pickup or delivery.',
                    'container_type' => $data['container_type'],
                    'unit' => 'gallon',
                    'price' => $data['price'],
                    'is_active' => true,
                ]
            );

            $product->inventory()->firstOrCreate([], ['quantity_on_hand' => $data['stock'], 'reorder_level' => 15]);
        }
    }
}
