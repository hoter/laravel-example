<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Поля: id, product_id (внешний ключ), user_name, rating (1-5), comment, is_approved, created_at, updated_at
        return [
            'product_id' => Product::factory(),
            'user_name' => fake()->userName(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->text(150),
            'is_approved' => fake()->boolean(90)
        ];
    }
}
