<?php

namespace App\Modules\ProcessSelection\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessSelection extends Model {

    protected $table='process_selection';
    protected $primarykey='id';
    public $fillable=[
    	'district_id',
    	'enrollment_id',
    	'application_id',
        'updated_by',
    	'form_id'
    ]; 

}
