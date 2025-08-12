<?php

Route::group(['prefix' => 'admin/LateSubmission','module' => 'LateSubmission', 'middleware' => ['web','auth'], 'namespace' => 'App\Modules\LateSubmission\Controllers'], function() {

    Route::get('/', 'LateSubmissionController@index');

    Route::get('/Process/Selection/validate/application/{application_id}', 'LateSubmissionController@validateApplication');

    Route::get('Generate/CDI/status', 'LateSubmissionController@checkLateCDIStatus');
    Route::get('Generate/Grade/status', 'LateSubmissionController@checkLateGradeStatus');
     Route::get('Generate/Priority/status', 'LateSubmissionController@checkLatePriorityStatus');

    Route::post('/Availability/store', 'LateSubmissionController@storeAllAvailability');

    Route::get('/Availability/Show/{form}', 'LateSubmissionController@show_all_availability');
    Route::get('/Availability/Show', 'LateSubmissionController@show_all_availability');

    Route::get('/Individual/Show/{form}', 'LateSubmissionController@show_all_individual');
    Route::get('/Individual/Show', 'LateSubmissionController@show_all_individual');

    Route::get('/Individual/Save/{program_id}/{grade}/{seats}', 'LateSubmissionController@saveIndividualAvailability');

    Route::get('/Individual/Show/Response/{program_id}', 'LateSubmissionController@individual_program_show');
    Route::post('/Individual/store', 'LateSubmissionController@storeIndividualAvailability');


    Route::get('/Population/Form', 'LateSubmissionController@population_change');
    Route::get('/Population/Form/{value}', 'LateSubmissionController@population_change');

    Route::get('/Population/Version/{version}', 'LateSubmissionController@population_change_version');
    Route::get('/Submission/Result/Version/{version}', 'LateSubmissionController@submissions_results_version');
    
    Route::get('/Submission/Result/{value}', 'LateSubmissionController@submissions_results');
    Route::get('/Submission/Result', 'LateSubmissionController@submissions_result');
    
    Route::get('/Submission/SeatsStatus/Version/{value}', 'LateSubmissionController@seatStatusVersion');
    Route::get('/Submission/Result', 'LateSubmissionController@submissions_result');
    Route::get('/SeatsStatus/Version/{value}', 'LateSubmissionController@seatStatusVersion');

    Route::post('/Accept/list', 'LateSubmissionController@selection_accept');
    Route::post('/Revert/list', 'LateSubmissionController@selection_revert');

    Route::post('/Send/Test/Mail', 'LateSubmissionEditCommunicationController@send_test_email');

    //Route::get('/Revert/list', 'ProcessSelectionController@selection_revert');

    Route::get('/EditCommunication/','LateSubmissionEditCommunicationController@index');

    Route::post('/EditCommunication/get/emails','LateSubmissionEditCommunicationController@fetchEmails');

    Route::get('/EditCommunication/download/{id}',  'LateSubmissionEditCommunicationController@downloadFile');

    Route::get('/EditCommunication/preview/{status}', 'LateSubmissionEditCommunicationController@previewPDF');
    Route::get('/EditCommunication/preview/email/{status}', 'LateSubmissionEditCommunicationController@previewEmail');


    Route::get('/EditCommunication/{status}','LateSubmissionEditCommunicationController@index');
    Route::post('/EditCommunication/store/letter','LateSubmissionEditCommunicationController@storeLetter');
    Route::post('/EditCommunication/store/email','LateSubmissionEditCommunicationController@storeEmail');
    Route::get('/EditCommunication/communicationPDF/{form_name}','LateSubmissionEditCommunicationController@generatePDF');
    Route::get('/EditCommunication/communicationEmail/{form_name}','LateSubmissionEditCommunicationController@generateEmail');
    
    Route::get('/EditCommunication/lettersLog','LateSubmissionEditCommunicationController@lettersLog');
    Route::get('/EditCommunication/deletePDF/{id}','LateSubmissionEditCommunicationController@deleteLettersLog');

    Route::get('/EditCommunication/emailsLog','LateSubmissionEditCommunicationController@emailsLog');
    Route::get('/EditCommunication/deleteemailsLog/{id}','LateSubmissionEditCommunicationController@deleteEmailsLog');


});