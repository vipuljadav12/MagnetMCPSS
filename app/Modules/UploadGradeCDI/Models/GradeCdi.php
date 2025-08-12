<?php

namespace App\Modules\UploadGradeCDI\Models;

use Illuminate\Database\Eloquent\Model;



class GradeCdi extends Model {

    //
    protected $table='grade_cdi_files';
    public $primaryKey='id';
    public $fillable=[
        'submission_id',
        'birthday',
        'file_type',
        'file_name',
    ];

    
}
