<?php

namespace Able\Helpers\Install\Features\Database;

use Able\Helpers\Install\Features\Feature;

abstract class DatabaseFeature extends Feature {

	public abstract function getConnectionString();
	public abstract function createDatabase();

} 
