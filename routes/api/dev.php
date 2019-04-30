<?php

Route::group(['prefix' => 'dev', 'namespace' => 'Dev'], function () {

    Route::group(['prefix' => 'responses'], function () {

        // index
        Route::get('', function () {
            return view('api/responses', ['title' => 'API Response Proposals']);
        });

        // output
        Route::get('output/format', 'ResponseController@format');
        Route::get('output/dummy', 'ResponseController@getDummyData');
        Route::get('output/input', 'ResponseController@input');
        Route::get('output/paginate', 'ResponseController@paginate');

        // empty vs 404
        Route::get('output/results', 'ResponseController@results');

        // validation
        Route::get('error/validation/request', 'ResponseController@validateRequest');
        Route::get('error/validation/data', 'ResponseController@validateData');

        // specific errors
        Route::get('error/api', 'ResponseController@apiError');
        Route::get('error/not-implemented', 'ResponseController@notImplemented');

        // uncaught
        Route::get('error/normal', 'ResponseController@normalError');
        Route::get('error/query', 'ResponseController@queryError');
        Route::get('error/uncaught', 'ResponseController@uncaughtError');

    });

});
