<?php

Route::group(['prefix'=>'admin/Program', 'module' => 'Program', 'middleware' => ['web','auth','super'], 'namespace' => 'App\Modules\Program\Controllers'], function() {

    Route::get('/', 'ProgramController@index');
    Route::get('/create', 'ProgramController@create');
    Route::post('/store', 'ProgramController@store');
    Route::get('/edit/{id}', 'ProgramController@edit');
    Route::get('/edit/{id}/{application_id}', 'ProgramController@edit');
    Route::post('/update/{id}', 'ProgramController@update');
    Route::get('/delete/{id}', 'ProgramController@delete');
    Route::get('/trash', 'ProgramController@trash');
    Route::get('/restore/{id}', 'ProgramController@restore');
    Route::get('/status', 'ProgramController@status');

    Route::get('/get/program/{enrollment_id}','ProgramController@fetchProgramByEnrollment');
});
