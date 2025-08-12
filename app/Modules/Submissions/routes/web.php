<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'admin/Submissions','module' => 'Submissions', 'middleware' => ['web','auth','permission'], 'namespace' => 'App\Modules\Submissions\Controllers'], function() {

    Route::get('/', 'SubmissionsController@index');
    Route::get('/getsubmissions', 'SubmissionsController@getSubmissions');
    
    Route::get('/test', 'SubmissionsController@testindex');

    Route::get('/edit/{id}', 'SubmissionsController@edit');
    Route::get('/override/grade', 'SubmissionsController@overrideGrade');
    Route::get('/override/cdi', 'SubmissionsController@overrideCDI');
    Route::post('/update/{id}', 'SubmissionsController@update');
    Route::post('/update/audition/{id}', 'SubmissionsController@updateAudition');
    Route::post('/update/WritingPrompt/{id}', 'SubmissionsController@updateWritingPrompt');
    Route::post('/update/InterviewScore/{id}', 'SubmissionsController@updateInterviewScore');
    Route::post('/update/CommitteeScore/{id}', 'SubmissionsController@updateCommitteeScore');
    Route::post('/update/ConductDisciplinaryInfo/{id}', 'SubmissionsController@updateConductDisciplinaryInfo');
    Route::post('/update/StandardizedTesting/{id}', 'SubmissionsController@updateStandardizedTesting');
    Route::post('/update/AcademicGradeCalculation/{id}', 'SubmissionsController@updateAcademicGradeCalculation');

    Route::post('/storegrades/{id}', 'SubmissionsController@storeGrades');
    Route::post('/store/comments/{id}', 'SubmissionsController@storeComments');
    Route::get('/confirmation/resend/{id}', 'SubmissionsController@resendConfirmationEmail');
    Route::get('/get/grades/program/{id}', 'SubmissionsController@fetchProgramGrade');
    Route::get('/get/grades/program/{id1}/{id2}', 'SubmissionsController@fetchProgramGrade');

    Route::get('/fetch/availability/{choice_id}/{grade}','SubmissionsController@checkAvailability');
    Route::get('/send/offer/email/{id}','SubmissionsController@sendCommunicationEmail');
    Route::get('/send/offer/email/{id}/{preview}','SubmissionsController@sendCommunicationEmail');
    Route::post('/store/manual/process/{id}', 'SubmissionsController@updateManualStatus');
    Route::post("store/manual/gradechange/{id}", 'SubmissionsController@updateNextgrade');

    Route::post('/general/send/offer/email/{waitlist}/{id}','SubmissionsController@sendGeneralCommunicationEmailPost');
    Route::get('/general/send/offer/email/{waitlist}/{id}/{preview}','SubmissionsController@sendGeneralCommunicationEmail');
    
    
    Route::get('/resend/email/{id}','SubmissionsController@resendCommunicationEmail');
    Route::get('/preview/email/{id}','SubmissionsController@previewCommunicationEmail');
    Route::get('/resendEmail/{id}','SubmissionsController@resendEmailCommunication');


    
});
Route::group(['prefix'=>'admin/Submissions','module' => 'Submissions', 'middleware' => ['web'], 'namespace' => 'App\Modules\Submissions\Controllers'], function() {

    Route::get('/transfer/grade', 'SubmissionsController@transferGradeStudentToSubmission');
});


