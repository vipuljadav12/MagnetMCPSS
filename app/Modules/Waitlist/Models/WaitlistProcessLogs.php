<?php

namespace App\Modules\Waitlist\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistProcessLogs extends Model {

    //
    protected $table='waitlist_process_logs';
    public $primaryKey='id';
    public $fillable=[
    	'enrollment_id',
    	'application_id',
    	'form_id',
    	'version',
        'program_id',
    	'last_date_online',
        'last_date_offline',
    	'generated_by'
   	];

}
