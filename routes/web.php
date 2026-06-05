<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/portfolio', function() {
    return 'Мое портфолио';
});

Route::get('/services', function() {
    return '<ul><li>1</li><li>2</li><li>3</li></ul>';
});

Route::get('/product/{id}', function(int $id) {
    return "Товар №{$id}";
})->whereNumber('id');

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