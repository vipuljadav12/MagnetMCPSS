<?php

namespace App\Modules\SetEligibility\Models;

use Illuminate\Database\Eloquent\Model;

class SetEligibility extends Model {

 	protected $table='set_eligibility';
    protected $primarykey='id';
    public $fillable=[
    	'district_id',
        'application_id',
    	'program_id',
    	'eligibility_type',
        'eligibility_id',
    	'required',
    	'eligibility_value',
    	'status',
    ]; 

}
