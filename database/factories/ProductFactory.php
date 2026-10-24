<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Round Gallon Refill', 'Slim Gallon Refill']),
            'description' => 'Purified drinking water.',
            'container_type' => fake()->randomElement(['Round Gallon', 'Slim Gallon']),
            'unit' => 'gallon',
            'price' => 30,
            'is_active' => true,
        ];
    }

    public function withStock(int $quantity = 50): static
    {
        return $this->afterCreating(function (Product $product) use ($quantity) {
            $product->inventory()->create(['quantity_on_hand' => $quantity, 'reorder_level' => 10]);
        });
    }
}
