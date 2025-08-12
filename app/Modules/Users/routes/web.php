<?php

Route::group(['prefix'=>'admin/Users','module' => 'Users', 'middleware' => ['web','auth'], 'namespace' => 'App\Modules\Users\Controllers'], function() {

	Route::get("/edit/{user}","UsersController@edit");
	Route::post("/update/{user}","UsersController@update");
	Route::post("/status/","UsersController@status");
	Route::get("/trash/{user}","UsersController@trash");
	Route::get("/trash","UsersController@trashindex");
    Route::get('/trash/restore/{user}', 'UsersController@restore');
    Route::resource('', 'UsersController');

    Route::post("/updateprofile/{id}","UsersController@UpdateProfile");
    Route::get("/uniqueemail","UsersController@uniqueemail");
    Route::get("/checkoldpass","UsersController@checkOldPass");


});
