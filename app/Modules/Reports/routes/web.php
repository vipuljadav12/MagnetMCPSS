<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'admin/Reports','module' => 'Reports', 'middleware' => ['web','auth'], 'namespace' => 'App\Modules\Reports\Controllers'], function() {
        Route::get('/admin_review', 'ReportsController@admin_index');

        Route::get('/missing/{id}/manual_grade_check', 'ReportsController@manualOverride');
        Route::get('/missing/{id}/manual_grade_check/response', 'ReportsController@manualOverrideResponse');

        Route::get('/export/manual_grade_check/{id}', 'ReportsController@manualOverrideResponse');
    Route::get('/export/manual_grade_check/{id}/{type}', 'ReportsController@manualOverrideResponse');


    Route::get('/missing/{id}/mcpss', 'ReportsController@mcpssSubmissions');

#    Route::get('/missing/{id}/grade', 'ReportsController@missingGradeMain');
#    Route::get('/missing/{id}/grade/response', 'ReportsController@missingGrade');

    Route::get('/missing/{id}/{cgrade}/grade', 'ReportsController@missingGradeMain');
    Route::get('/missing/{id}/{cgrade}/grade/response', 'ReportsController@missingGrade');

    
    Route::get('/missing/{id}/cdi', 'ReportsController@missingCDIMain');
    Route::get('/missing/{id}/cdi/response', 'ReportsController@missingCDI');

    Route::get('/missing/{id}/allcdi', 'ReportsController@allCDIReport');
    Route::get('/missing/{id}/allcdi/response', 'ReportsController@allCDIReportResponse');
    Route::get('/export/allcdi/{id}', 'ReportsController@allCDIReportResponse');
    Route::get('/export/allcdi/{id}/{type}', 'ReportsController@allCDIReportResponse');

    Route::get('/missing/{id}/denied_due_to_ineligibility', 'ReportsController@missingDeniedEligibilityIndex');
    Route::get('/missing/{id}/denied_due_to_ineligibility/{application_id}', 'ReportsController@missingDeniedEligibilityMain');
    Route::get('/missing/{id}/denied_due_to_ineligibility/{application_id}/response', 'ReportsController@missingDeniedEligibilityMainResponse');



    Route::get('/missing/{id}/offerstatus', 'ReportsController@offerStatus');
    Route::get('/missing/{id}/offerstatus/{version}', 'ReportsController@offerStatus');
    Route::get('/missing/{id}/offerstatus/{type}/{version}', 'ReportsController@offerStatus');
    
    Route::get('/missing/{id}/seatstatus', 'ReportsController@seatStatus');
    
    Route::get('/process/logs', 'ReportsController@processingLogsReport');

    Route::get('/missing/{id}/duplicatestudent', 'ReportsController@duplicate_student');
    Route::get('/missing/{id}/duplicatestudent/{type}', 'ReportsController@duplicate_student');
   


    Route::get('/missing/{id}/mcpss/verification/{status}', 'ReportsController@mcpssEmployeeVerification');
    Route::get('/missing/{id}/mcpss/changeStatus', 'ReportsController@mcpssEmployeeStatus');

    

    Route::get('/export/missinggrade/{id}/{cgrade}', 'ReportsController@missingGrade');
    Route::get('/export/missinggrade/{id}/{cgrade}/{type}', 'ReportsController@missingGrade');

    Route::get('/export/missingcdi/{id}', 'ReportsController@missingCDI');
    Route::get('/export/missingcdi/{id}/{type}', 'ReportsController@missingCDI');

    Route::get('/missing/{id}/populationchange', 'ReportsController@populationChange');
    Route::get('/missing/{id}/results', 'ReportsController@submissionResults');


    Route::get('/setting/update/{field}/{val}', 'ReportsController@settingUpdate');


    Route::get('/', 'ReportsController@index');
    Route::get('/newreport', 'ReportsController@newreport');
    
    Route::get('/missing', 'ReportsController@missing_index');
    
    #Route::get('/missing/grade/{program_id}', 'ReportsController@missingGrade');
    Route::get('/import/missing/{id}/grade', 'ReportsController@importGradeGet');
    Route::post('/import/missing/grade', 'ReportsController@importGrade');
    Route::get('/import/missing/{id}/cdi', 'ReportsController@importCDIGet');
    Route::post('/import/missing/cdi', 'ReportsController@importCDI');

    Route::post('/missing/grade/save/{id}', 'ReportsController@saveGrade');
    Route::post('/missing/cdi/save/{id}', 'ReportsController@saveCDI');


    #Route::get('/missing/cdi', 'ReportsController@missingCDI');
    #Route::get('/missing/cdi/{program_id}', 'ReportsController@missingCDI');

    Route::get('/{grade}', 'ReportsController@index');
    Route::get('/export', 'ReportsController@index');

    Route::get('/missing/{id}/gradecdiupload', 'ReportsController@gradeCdiUploadList');
    Route::get('/missing/{id}/gradecdiupload/{type}/confirmed', 'ReportsController@gradeCdiUploadConfirmed');
    

    Route::get('/missing/{id}/all_powerschool_cdi', 'ReportsController@allPowerSchoolCDIReport');
    Route::get('/missing/{id}/all_powerschool_cdi/response', 'ReportsController@allPowerSchoolCDIReportResponse');

   /* Route::get('/edit/{id}', 'SubmissionsController@edit');
    Route::post('/update/{id}', 'SubmissionsController@update');
    Route::post('/update/audition/{id}', 'SubmissionsController@updateAudition');
    Route::post('/update/WritingPrompt/{id}', 'SubmissionsController@updateWritingPrompt');
    Route::post('/update/InterviewScore/{id}', 'SubmissionsController@updateInterviewScore');
    Route::post('/update/CommitteeScore/{id}', 'SubmissionsController@updateCommitteeScore');
    Route::post('/update/ConductDisciplinaryInfo/{id}', 'SubmissionsController@updateConductDisciplinaryInfo');
    Route::post('/update/StandardizedTesting/{id}', 'SubmissionsController@updateStandardizedTesting');
    Route::post('/update/AcademicGradeCalculation/{id}', 'SubmissionsController@updateAcademicGradeCalculation');

    Route::post('/storegrades/{id}', 'SubmissionsController@storeGrades');*/
    
});
