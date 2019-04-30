<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['namespace' => 'Api'], function () {

    Route::group([
        'prefix'     => 'api/v2',
        'namespace'  => 'v2',
        'middleware' => 'session',
    ], function () {

        require 'api/dev.php';
        require 'api/v2.php';

    });

});

Route::group(['namespace' => 'Web'], function () {

    Route::get('/', function () {
        return [
            'message' => "Harlib Front Office API",
            'route'   => url('/'),
        ];
    });

});

Route::get('forget-password', 'AuthController@forgetPassword')->name('forget-password');
Route::post('send-password-reset-link', 'AuthController@sendPasswordResetLink')->name('send-password-reset-link');
Route::get('reset-password',  'AuthController@resetPassword')->name('reset-password');
Route::post('update-password',  'AuthController@updatePassword')->name('update-password');

Route::group(['namespace' => 'Admin', 'prefix'=> 'admin'], function(){
    Route::get('/login', 'AuthController@login')->name('login');
    Route::post('/login', 'AuthController@attempt')->name('attempt');
    Route::get('/logout', 'AuthController@logout')->middleware('custom.auth')->name('logout');
});

//Admin
Route::group(['middleware'=> ['custom.auth'], 'namespace' => 'Admin', 'prefix' => 'admin'], function () {
	Route::get('/', 'HomeController@index')->name('home');
    Route::post('/upload-stock-feed','StockFeedController@upload')->name('uploadStockFeed');
	Route::get('/gforce-stock','StockFeedController@importGforceStockFeed')->name('gforceStockFeed');
	
	//Car Model
	Route::get('/car-model','CarModelController@index')->name('carModel');
	Route::post('/get-cars','CarModelController@getCars')->name('getCars');
	Route::post('/edit-car-detail','CarModelController@editCarDetails')->name('editCarDetails');
});




