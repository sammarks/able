<?php

namespace Able\Helpers\Install\Features\Database;

use Able\Helpers\Install\Features\Feature;

abstract class DatabaseFeature extends Feature {

	/**
	 * The hostname.
	 * @var string
	 */
	protected $host;

	/**
	 * The username.
	 * @var string
	 */
	protected $username;

	/**
	 * The password.
	 * @var string
	 */
	protected $password;

	/**
	 * The name of the database.
	 * @var string
	 */
	protected $database;

	/**
	 * Whether or not to automatically create the database.
	 * @var bool
	 */
	protected $create = false;

	/**
	 * Get Connection String
	 *
	 * Gets the connection string for connecting to the database.
	 *
	 * @return string
	 */
	public abstract function getConnectionString();

	/**
	 * Create Database
	 *
	 * Creates the database based on the supplied credentials.
	 */
	public abstract function createDatabase();

	public function preCopy($directory)
	{
		// Create the database before everything else if it has been requested.
		if ($this->create) {
			$this->createDatabase();
		}
	}
}

class DatabaseFeatureException extends \Exception {}
