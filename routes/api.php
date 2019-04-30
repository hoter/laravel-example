<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['namespace' => 'Api'], function () {

    return;

    Route::group([
        'prefix' => 'v2',
        'namespace' => 'v2',
        'middleware' => 'session',
    ], function () {

        require 'api/dev.php';
        require 'api/v2.php';

        });

});

