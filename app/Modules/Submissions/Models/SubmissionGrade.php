<?php

namespace App\Modules\Submissions\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionGrade extends Model {
    public $timestamps = false;
    protected $table='submission_grade';
    protected $primaryKey='id';
    protected $fillable=[
    	'submission_id',
        'stateID',
    	'academicYear',
        'GradeName',
    	'academicTerm',
    	'courseTypeID',
    	'courseType',
    	'courseName',
        'courseFullName',
    	'sectionNumber',
    	'numericGrade',
    	'use_in_calculations',
    ];

}
