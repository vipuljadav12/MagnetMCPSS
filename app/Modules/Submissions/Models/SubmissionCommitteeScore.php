<?php

namespace App\Modules\Submissions\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionCommitteeScore extends Model {

    //
    protected $table='submission_committee_score';
    protected $primaryKey='id';
    protected $fillable=[
    	'submission_id',
    	'data'
    ];

}
