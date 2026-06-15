<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    $projects = [
        ['id' => 1, 'title' => 'Task Manager', 'description' => 'Приложение для управления задачами', 'tech' => ['PHP', 'Laravel', 'MySQL'], 'github' => 'https://github.com/user/task-manager'],
        ['id' => 2, 'title' => 'Blog Platform', 'description' => 'Платформа для ведения блога', 'tech' => ['Laravel', 'Blade', 'Tailwind'], 'github' => 'https://github.com/user/blog-platform'],
        ['id' => 3, 'title' => 'Weather API', 'description' => 'Сервис для получения погоды', 'tech' => ['PHP', 'Guzzle', 'API'], 'github' => 'https://github.com/user/weather-api'],
    ];
    return view('welcome', ['projects' => $projects]);
});

Route::get('/projects', function () {
    $projects = [
        ['id' => 1, 'title' => 'Task Manager', 'description' => 'Приложение для управления задачами', 'tech' => ['PHP', 'Laravel', 'MySQL'], 'github' => 'https://github.com/user/task-manager'],
        ['id' => 2, 'title' => 'Blog Platform', 'description' => 'Платформа для ведения блога', 'tech' => ['Laravel', 'Blade', 'Tailwind'], 'github' => 'https://github.com/user/blog-platform'],
        ['id' => 3, 'title' => 'Weather API', 'description' => 'Сервис для получения погоды', 'tech' => ['PHP', 'Guzzle', 'API'], 'github' => 'https://github.com/user/weather-api'],
    ];
    return view('projects', ['projects' => $projects]);
});

Route::get('/projects/{id}', function (int $id) {
    $projects = [
        ['id' => 1, 'title' => 'Task Manager', 'description' => 'Приложение для управления задачами', 'tech' => ['PHP', 'Laravel', 'MySQL'], 'github' => 'https://github.com/user/task-manager'],
        ['id' => 2, 'title' => 'Blog Platform', 'description' => 'Платформа для ведения блога', 'tech' => ['Laravel', 'Blade', 'Tailwind'], 'github' => 'https://github.com/user/blog-platform'],
        ['id' => 3, 'title' => 'Weather API', 'description' => 'Сервис для получения погоды', 'tech' => ['PHP', 'Guzzle', 'API'], 'github' => 'https://github.com/user/weather-api'],
    ];
    return view('project', ['project' => $projects[$id]]);
})->whereNumber('id');

Route::get('/contacts', function() {
    return view('contacts');
});

Route::get('/about', function() {
    return view('about');
});

Route::get('/portfolio', function() {
    return 'Мое портфолио';
});

Route::get('/services', function() {
    return '<ul><li>1</li><li>2</li><li>3</li></ul>';
});

Route::get('/product/{id}', [ProductController::class, 'show']);

Route::get('/blog/{category}/{slug}', function($category, $slug) {
    return "Товар №{$slug} из категории {$category}";
});

Route::get('/search/{query?}', function(string $query = "Введите поисковый запрос") {
  return $query;
});

Route::get('/dashboard', function() {
    return "dashboard";
})->name('dashboard');

Route::prefix('admin')->group(function() {
    Route::get('/users', function() {
        return "users";
    });
    Route::get('/pc', function() {
        return "pc";
    });
    Route::get('/dzen', function() {
        return "dzen";
    });
});

Route::resource('products', ProductController::class);
