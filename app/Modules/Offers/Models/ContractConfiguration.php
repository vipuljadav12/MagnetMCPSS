<?php

namespace App\Modules\Offers\Models;

use Illuminate\Database\Eloquent\Model;

class ContractConfiguration extends Model {
	protected $table = 'contract_configuration';

	protected $fillable = [
		'district_id',
		'enrollment_id',
		'header_text',
		'footer_text',
		'title_text',
		'extra'
	];

	protected $casts = [
        'extra' => 'array',
    ];
}
