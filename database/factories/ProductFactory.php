<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;

/**
 * @extends Factory<Product>
 */
#[UseModel(Product::class)]
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'sku' => fake()->numerify('SKU-####'),
            'description' => fake()->paragraph(3, true),
            'price' => fake()->numberBetween(100, 10000),
            'old_price' => fake()->numberBetween(200, 15000),
            'stock' => fake()->numberBetween(0, 500),
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => fake()->numberBetween(1, 500),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(function(array $attributes)
        {
            if (empty($attributes['price'])) {
                $price = fake()->numberBetween(100, 10000);
            }
            else {
                $price = $attributes['price'];
            }

            return [
                'price' => $price,
                'old_price' => fake()->numberBetween($price + 1,$price * 2),
            ];
        });
    }
}
