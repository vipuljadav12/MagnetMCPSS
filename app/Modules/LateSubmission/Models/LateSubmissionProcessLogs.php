<?php

namespace App\Modules\LateSubmission\Models;

use Illuminate\Database\Eloquent\Model;

class LateSubmissionProcessLogs extends Model
{
    protected $table = 'late_submission_process_logs';
    public $primaryKey = 'id';
    public $fillable = [
        'enrollment_id',
        'application_id',
        'form_id',
        'version',
        'program_id',
        'last_date_online',
        'last_date_offline',
        'auto_decline_cron',
        'generated_by'
    ];
}
