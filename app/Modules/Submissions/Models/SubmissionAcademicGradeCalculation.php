<?php

namespace App\Modules\Submissions\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionAcademicGradeCalculation extends Model {
    protected $table='submission_academic_grade_calculation';
    protected $primaryKey='id';
    protected $fillable=[
    	'submission_id',
    	'subjects',
    	'gpa',
    	'average_score',
    	'given_score',
    	'scoring_type'
    ];

}
