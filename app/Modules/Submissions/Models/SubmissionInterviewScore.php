<?php

namespace App\Modules\Submissions\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionInterviewScore extends Model {

    //
    protected $table='submission_interview_score';
    protected $primaryKey='id';
    protected $fillable=[
    	'submission_id',
    	'data'
    ];

}
