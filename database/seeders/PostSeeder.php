<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Создать 50 обычных постов
        Post::factory()->count(50)->create();

        // Создать 10 популярных опубликованных постов
        Post::factory()
        ->count(10)
            ->published()
            ->withViews(5000, 100000)
            ->create();

        // Создать 5 черновиков без просмотров
        Post::factory()
        ->count(5)
            ->draft()
            ->withViews(0, 0)
            ->create();
    }
}
