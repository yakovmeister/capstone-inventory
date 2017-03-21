<?php

namespace Capstone;


use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container as Container;

class Database
{

	/**
	 * Connection driver and information we're going to use
	 * @var [type]
	 */
	protected $connection = [
		'driver'    => 'sqlite',							
		'database'  => 'database/debug.db',
	];

	public function __construct()
	{
		$capsule = new Capsule;


		$capsule->addConnection($this->getConnection());

		// Make this Capsule instance available globally via static methods... (optional)
		$capsule->setAsGlobal();

		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
		$capsule->bootEloquent();
	}

	/**
	 * Get the connection array.
	 * @return array [connection settings saved in an array]
	 */
	public function getConnection()
	{
		return $this->connection;
	}
}