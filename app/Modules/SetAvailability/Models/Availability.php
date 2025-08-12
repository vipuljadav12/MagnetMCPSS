<?php

namespace App\Modules\SetAvailability\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model {

    protected $table = "availability";
    protected $fillable = [
    	"program_id",
    	"district_id",
    	"enrollment_id",
    	"grade",
    	"available_seats",
    	"total_seats",
    	"year"
    ];
}
