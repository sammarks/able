<?php

namespace Able\Helpers\Install\Features\Database;

use Able\Helpers\Install\Features\Feature;

abstract class DatabaseFeature extends Feature {

	protected $host;
	protected $username;
	protected $password;
	protected $database;
	protected $create = false;

	public abstract function getConnectionString();
	public abstract function createDatabase();

} 

class DatabaseFeatureException extends \Exception {}
