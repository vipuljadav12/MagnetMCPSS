<?php

use Illuminate\Routing\Route;

Route::group(['module' => 'Eligibility', 'middleware' => ['api'], 'namespace' => 'App\Modules\Eligibility\Controllers'], function() {

    Route::resource('Eligibility', 'EligibilityController');

});
