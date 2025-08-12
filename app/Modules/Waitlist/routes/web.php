<?php

Route::group(['prefix' => 'admin/Waitlist','module' => 'Waitlist', 'middleware' => ['web','auth'], 'namespace' => 'App\Modules\Waitlist\Controllers'], function() {

    Route::get('/', 'WaitlistController@index');
       Route::get('/Process/Selection/validate/application/{application_id}', 'WaitlistController@validateApplication');


    Route::post('/Availability/store', 'WaitlistController@storeAllAvailability');

    Route::get('/Availability/Show/{form}', 'WaitlistController@show_all_availability');
    Route::get('/Availability/Show', 'WaitlistController@show_all_availability');

    Route::get('/Individual/Show/{form}', 'WaitlistController@show_all_individual');
    Route::get('/Individual/Show', 'WaitlistController@show_all_individual');

    Route::get('/Individual/Save/{program_id}/{grade}/{seats}', 'WaitlistController@saveIndividualAvailability');

    Route::get('/Individual/Show/Response/{program_id}', 'WaitlistController@individual_program_show');
    Route::post('/Individual/store', 'WaitlistController@storeIndividualAvailability');


    Route::get('/Population/Form', 'WaitlistController@population_change');
    Route::get('/Population/Form/{value}', 'WaitlistController@population_change');

    Route::get('/Population/Version/{version}', 'WaitlistController@population_change_version');
    Route::get('/Submission/Result/Version/{version}', 'WaitlistController@submissions_results_version');
    
    Route::get('/Submission/Result/{value}', 'WaitlistController@submissions_results');
    Route::get('/Submission/Result', 'WaitlistController@submissions_results');
    
    Route::get('/Submission/SeatsStatus/Version/{value}', 'WaitlistController@seatStatusVersion');
    #Route::get('/Submission/Result', 'WaitlistController@submissions_result');

    Route::post('/Accept/list', 'WaitlistController@selection_accept');
    Route::post('/Revert/list', 'WaitlistController@selection_revert');

    Route::post('/Send/Test/Mail', 'WaitlistEditCommunicationController@send_test_email');

    //Route::get('/Revert/list', 'ProcessSelectionController@selection_revert');

    Route::get('/EditCommunication/','WaitlistEditCommunicationController@index');

    Route::post('/EditCommunication/get/emails','WaitlistEditCommunicationController@fetchEmails');

    Route::get('/EditCommunication/download/{id}',  'WaitlistEditCommunicationController@downloadFile');

    Route::get('/EditCommunication/preview/{status}', 'WaitlistEditCommunicationController@previewPDF');
    Route::get('/EditCommunication/preview/email/{status}', 'WaitlistEditCommunicationController@previewEmail');


    Route::get('/EditCommunication/{status}','WaitlistEditCommunicationController@index');
    Route::post('/EditCommunication/store/letter','WaitlistEditCommunicationController@storeLetter');
    Route::post('/EditCommunication/store/email','WaitlistEditCommunicationController@storeEmail');
    Route::get('/EditCommunication/communicationPDF/{form_name}','WaitlistEditCommunicationController@generatePDF');
    Route::get('/EditCommunication/communicationEmail/{form_name}','WaitlistEditCommunicationController@generateEmail');
    
    Route::get('/EditCommunication/lettersLog','WaitlistEditCommunicationController@lettersLog');
    Route::get('/EditCommunication/deletePDF/{id}','WaitlistEditCommunicationController@deleteLettersLog');

    Route::get('/EditCommunication/emailsLog','WaitlistEditCommunicationController@emailsLog');
    Route::get('/EditCommunication/deleteemailsLog/{id}','WaitlistEditCommunicationController@deleteEmailsLog');


});