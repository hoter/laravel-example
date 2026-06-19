<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Tag;
use App\Models\Comment;

/**
 * @extends Factory<Post>
 */
#[UseModel(Post::class)]
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'user_id' => User::factory(),
            'slug' => fake()->slug(2) . '-' . fake()->numberBetween(1, 9999),
            'content' => fake()->paragraphs(5, true),
            'excerpt' => fake()->sentence(15),
            'views' => fake()->numberBetween(0, 5000),
            'is_published' => fake()->boolean(70),
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Post $post) {
            if (Tag::count() === 0) {
                Tag::factory(10)->create();
            }

            $tagIds = Tag::inRandomOrder()
                ->take(rand(1, 3))
                ->pluck('id');

            $post->tags()->attach($tagIds);

            Comment::factory()->count(rand(1, 5))->for($post)->create();
        });
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function withViews(int $min = 100, int $max = 10000): static
    {
        return $this->state(fn(array $attributes) => [
            'views' => fake()->numberBetween($min, $max),
        ]);
    }
}
