<?php

namespace App\Modules\Program\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $table = 'program';
    public $primaryKey = 'id';
    public $fillable = [
        'district_id',
        'enrollment_id',
        'name',
        'applicant_filter1',
        'applicant_filter2',
        'applicant_filter3',
        'grade_lavel',
        'parent_submission_form',
        'priority',
        'committee_score',
        'audition_score',
        'rating_priority',
        'combine_score',
        'lottery_number',
        'final_score',
        'selection_method',
        'selection_by',
        'seat_availability_enter_by',
        'basic_method_only',
        'combined_scoring',
        'combined_eligibility',
        'magnet_school',
        'created_at',
        'updated_at',
        'status',
        'silbling_check',
        'sibling_enabled',
        'existing_magnet_program_alert',
        'exclude_grade_lavel',
        'feeder_priorities',
        'current_over_new',
        'magnet_priorities'

    ];
}


/*namespace App\Modules\ProgramEligibility\Models;


use Illuminate\Database\Eloquent\Model;

class ProgramEligibility extends Model
{

    //
    protected $table = 'program_eligibility';
    protected $primaryKey = 'id';
    protected $fillable = [
        'program_id',
        'eligibility_type',
        'determination_method',
        'assigned_eigibility_name',
        'grade_level',
        'weight',
        'status',
        'created_at',
        'updated_at',
        'status',
    ];

}*/