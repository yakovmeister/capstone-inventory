<?php

namespace Capstone\Model;

class User extends \Illuminate\Database\Eloquent\Model 
{
	public $timestamps = false;

	protected $fillable = [
		'username',
		'password'
	];
}

