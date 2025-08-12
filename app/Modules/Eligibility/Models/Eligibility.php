<?php

namespace App\Modules\Eligibility\Models;

use Illuminate\Database\Eloquent\Model;

class Eligibility extends Model {

    //
    protected $table='eligibiility';
    public $primaryKey='id';
    public $fillable=[
        'template_id',
        'enrollment_id',
      	'name',
      	'type',
      	'district_id',
      	'store_for',
        'override',
      	'status'
      	// 'created_at',
      	// 'updated_at',
    ];

}
