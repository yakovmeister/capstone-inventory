<?php

namespace Capstone\Model;

class Withdraw extends \Illuminate\Database\Eloquent\Model 
{
	public $timestamps = false;

	protected $table = "withdraw";
	
	protected $fillable = [
		'item_id',
		'date_issued',
		'name',
		'designation',
		'receiver',
		'requestor',
		'purpose',
		'stock'
	];

	public function item()
	{
		return $this->belongsTo('Capstone\\Model\\Item');
	}
}

