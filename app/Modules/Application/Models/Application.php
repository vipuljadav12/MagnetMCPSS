<?php

namespace App\Modules\Application\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
	protected $table = 'application';
	public $date_fields = [
		'starting_date',
		'ending_date',
		'admin_starting_date',
		'admin_ending_date',
		'recommendation_due_date',
		'transcript_due_date',
		'cdi_starting_date',
		'cdi_ending_date'
	];
	public $primary_key = 'id';
	public $fillable = [
		'district_id',
		'application_name',
		'form_id',
		'enrollment_id',
		'starting_date',
		'ending_date',
		'fetch_grades_cdi',
		'admin_starting_date',
		'admin_ending_date',
		'recommendation_due_date',
		'transcript_due_date',
		'magnet_url',
		'display_logo',
		'cdi_starting_date',
		'cdi_ending_date',
		'submission_type',
		'created_at',
		'updated_at'
	];
}
