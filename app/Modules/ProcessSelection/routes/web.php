<?php

Route::group(['prefix' => 'admin/Process/Selection','module' => 'ProcessSelection', 'middleware' => ['web','auth'], 'namespace' => 'App\Modules\ProcessSelection\Controllers'], function() {

    Route::get('/', 'ProcessSelectionController@index');
    Route::post('/store', 'ProcessSelectionController@store');

    Route::get('/Population', 'ProcessSelectionController@population_change_form');
    Route::get('/Population/{value}', 'ProcessSelectionController@population_change');
    Route::get('/Population/Form/{value}', 'ProcessSelectionController@population_change_form');
    Route::get('/Population/Form', 'ProcessSelectionController@population_change_form');
    
    Route::get('/Results/Form', 'ProcessSelectionController@submissions_results_form');

    Route::get('/Results/{value}', 'ProcessSelectionController@submissions_results');
    Route::get('/Results/Form/{value}', 'ProcessSelectionController@submissions_results_form');

    Route::post('/Accept/list', 'ProcessSelectionController@selection_accept');
   // Route::post('/Revert/list', 'ProcessSelectionController@selection_revert');
    //Route::get('/Revert/list', 'ProcessSelectionController@selection_revert');

});
