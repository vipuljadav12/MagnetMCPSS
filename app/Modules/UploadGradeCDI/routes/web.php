<?php

//Route::group(['prefix'=>'admin/upload','module' => 'UploadGradeCdi', 'middleware' => ['web','auth','permission'], 'namespace' => 'App\Modules\UploadGradeCdi\Controllers'], function() {

Route::group(['prefix'=>'upload','module' => 'UploadGradeCdi', 'middleware' => ['web'], 'namespace' => 'App\Modules\UploadGradeCDI\Controllers'], function() {

    Route::get('/grade/cdi', 'UploadGradeCDIController@index');
    Route::get('/{id}/grade/cdi', 'UploadGradeCDIController@index');
    Route::post('/grade/cdi/checkSubmissionId','UploadGradeCDIController@checkSubmissionId');
    Route::post('/grade/cdi/uploadFiles','UploadGradeCDIController@uploadFiles');
});


