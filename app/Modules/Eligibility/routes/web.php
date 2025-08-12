<?php
use App\Modules\Application\Models\Application;
use App\Modules\SetEligibility\Models\SetEligibility;

Route::group([ 'prefix'=>'admin/Eligibility','module' => 'Eligibility', 'middleware' => ['web','auth'], 'namespace' => 'App\Modules\Eligibility\Controllers'], function() {

    Route::get('/', 'EligibilityController@index');
    Route::get('/create', 'EligibilityController@create');
    Route::get('/getTemplateHtml/{id}', 'EligibilityController@getTemplateHtml');
   Route::post('/store', 'EligibilityController@store');
    Route::get('/edit/{eligibility}', 'EligibilityController@edit');
    Route::post('/update/{id}', 'EligibilityController@update');
    Route::get('/delete/{id}', 'EligibilityController@delete');
    Route::get('/trash', 'EligibilityController@trash');
    Route::get('/restore/{id}', 'EligibilityController@restore');
    Route::get('/view/{id}', 'EligibilityController@view');
    Route::get('/status', 'EligibilityController@status');
    Route::post('/checkEligiblityName', 'EligibilityController@checkEligiblityUnique');

    Route::get('/subjectManagement/{id?}', 'SubjectManagementController@index');
    Route::post('/updateSubjectManagement', 'SubjectManagementController@updateSubjectManagement');

    Route::get('/test',function(){
        $old_application_ids_array = Application::where('district_id',session('district_id'))->where('enrollment_id',15)->pluck('id');
        $set_eligibility = SetEligibility::where('district_id',session('district_id'))->whereIn('application_id',$old_application_ids_array)->get();

        return $set_eligibility;
    });

});
