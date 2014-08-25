<?php

namespace Able\Helpers\Install\Features;

use Able\CommandSets\BaseCommand;
use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\ConfigurationManager;
use Able\Helpers\GlobalKnowledge\GlobalKnowledge;
use Able\Helpers\Install\Features\DatabaseFeature;
use Able\Helpers\Install\Features\DatabaseFeatureException;

class MySQLDatabaseFeature extends DatabaseFeature {

	public function getConnectionString()
	{
		$username = urlencode($this->username);
		$password = urlencode($this->password);
		$database = urlencode($this->database);
		$host = urlencode($this->host);

		return "mysql://$username:$password@$host/$database";
	}

	public function postSetConfiguration()
	{
		$this->username = $this->configuration['username'];
		$this->password = $this->configuration['password'];
		$this->database = $this->configuration['database'];
		$this->host = $this->configuration['host'];
		$this->create = $this->configuration['create'];
	}

	public function createDatabase()
	{
		$username = ConfigurationManager::getInstance()->get('databases/mysql/' . $this->host . '/username');
		if ($username === null) {
			throw new DatabaseFeatureException('Cannot create the database. The root username key at databases/mysql/' . $this->host . '/username could not be found.');
		}

		$password = ConfigurationManager::getInstance()->get('databases/mysql/' . $this->host . '/password');
		if ($password === null) {
			throw new DatabaseFeatureException('Cannot create the database. The root password key at databases/mysql/' . $this->host . '/password could not be found.');
		}

		$pdo = new \PDO("mysql:host={$this->host}", $username, $password);
		$pdo->exec("CREATE DATABASE `{$this->database}`;");
		$pdo->exec("GRANT ALL ON `{$this->database}`.* to '{$this->username}'@'{$this->host}' IDENTIFIED BY '{$this->password}';");

		Logger::getInstance()->log('Created database successfully.', 'white', Logger::DEBUG_VERBOSE);
	}

	public function didDatabaseExist()
	{
		if ($this->was_created === true) return false;

		try {
			new \PDO("mysql:host={$this->host};dbname={$this->database}", $this->username, $this->password);
		} catch (\PDOException $ex) {
			return false;
		}

		return true;
	}

}
