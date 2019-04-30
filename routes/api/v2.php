<?php

// frontend
Route::get('frontend/get/{any}', 'FrontendController@get')
    ->where('any', '.*');

// sitemap
Route::get('sitemap/index', 'SitemapController@index');
Route::get('sitemap/static', 'SitemapController@staticPages');
Route::get('sitemap/blog-posts', 'SitemapController@blogPosts');
Route::get('sitemap/car-makes', 'SitemapController@carMakes');
Route::get('sitemap/cars', 'SitemapController@cars');

// app
Route::get ('app/settings', 'AppController@settings');
Route::match(['get', 'post'], 'app/session', 'AppController@session');

// auth
Route::group(['namespace' => 'Auth'], function () {
    Route::post('auth/login', 'AuthController@login');
    Route::get ('auth/logout', 'AuthController@logout');
    Route::get ('auth/refresh', 'AuthController@refresh');
    Route::get('auth/reset-password', 'AuthController@sendPasswordResetLink');
    Route::post('auth/reset-password', 'AuthController@setNewPassword');
});

// account
Route::group(['namespace' => 'Account'], function () {
    Route::post('account/create', 'AccountController@create');
    Route::post ('account/applications/{id}/complete-payment', 'PaymentController@completeSecurePayment');
});

// finance
Route::group(['namespace' => 'Finance'], function () {

    // rating
    Route::get ('finance/rating', 'FinanceController@getRating');
    Route::post('finance/rating', 'FinanceController@setRating');

    // product
    Route::post('/product/search', 'FinanceController@product');

    // get APR
    Route::post('finance/get-estimate', 'FinanceController@estimate');
});

// cars
Route::group(['namespace' => 'Cars'], function () {
    Route::post('cars/search', 'CarController@search');
    Route::post('cars/samples', 'CarController@getCarSamples');
    Route::post('cars/browse', 'CarController@browse');
    Route::post('cars/view/{id}', 'CarController@getCar');
    Route::post('cars/price/{id}', 'CarController@getPrice');
    Route::get ('cars/status/{id}', 'CarController@getStatus');
    Route::get ('cars/makes', 'CarController@getMakes');
    Route::get ('cars/models/{make}', 'CarController@getModels');
    Route::get ('cars/by-name/{car_type}/{name}', 'CarController@getByName');
    //get image api
    Route::get('cars/get-image', 'CarController@getImage');
	Route::get('cars/get-similar-stock/{id}', 'CarController@getSimilarStock');
});

// protected routes
Route::group(['middleware' => ['jwt.auth']], function () {

    // finance
    Route::group(['namespace' => 'Finance'], function () {

        // misc
        Route::post('finance/likely-offers', 'FinanceController@getLikelyOffers');
        Route::post('finance/finance-only-offers', 'FinanceController@getFinanceOnlyOffers');
        Route::get('finance/email-finance-only-offers/{applicationId}', 'FinanceController@sendFinanceOffer');

        // financial application
        Route::get ('finance/application/deposit', 'FinanceController@checkDeposit');
        Route::post('finance/application/create', 'ApplicationController@create');
        Route::get ('finance/application/{applicationId}/offers', 'ApplicationController@getOffers');
        Route::post('finance/application/{applicationId}/offers/{lenderId}/fetch', 'ApplicationController@fetchOffer');
        Route::post('finance/application/{applicationId}/offers/{lenderId}/choose', 'ApplicationController@chooseOffer');
    });

    // account
    Route::group(['namespace' => 'Account'], function () {

        // data
        Route::get ('account', 'AccountController@index');
        Route::post('account/personal', 'AccountController@personal');
        Route::post('account/addresses', 'AccountController@addresses');
        Route::post('account/employment', 'AccountController@employment');
        Route::post('account/finances', 'AccountController@finances');

        // application
        Route::resource('account/applications', 'ApplicationController');
        Route::get ('account/applications/{id}', 'ApplicationController@show');

        // conversion
        Route::get ('account/applications/{id}/questions', 'ApplicationController@getQuestions');
        Route::post('account/applications/{id}/questions', 'ApplicationController@postAnswers');
        Route::post('account/applications/{id}/verify-id', 'ApplicationController@checkId');
        Route::post('account/applications/{id}/upload-docs', 'ApplicationController@submitDocuments');

        // payment
        Route::post ('account/applications/{id}/payment', 'PaymentController@makePayment');
        Route::post ('account/applications/{id}/start-payment', 'PaymentController@startSecurePayment');
    });
});

