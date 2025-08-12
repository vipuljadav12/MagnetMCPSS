<?php

namespace App\Modules\Waitlist\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistAvailabilityProcessLog extends Model {

    protected $table = "availability_process_logs";
    protected $fillable = [
    	"program_id",
        "enrollment_id",
    	"grade",
    	"waitlist_count",
        "type",
    	"withdrawn_seats",
    	"offered_count",
    	"total_capacity",
    	"version"
    ];
}
