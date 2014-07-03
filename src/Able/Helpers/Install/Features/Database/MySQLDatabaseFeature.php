<?php

namespace Able\Helpers\Install\Features\Database;

use Able\CommandSets\BaseCommand;
use Able\Helpers\GlobalKnowledge\GlobalKnowledge;

class MySQLDatabaseFeature extends DatabaseFeature {

	public function getConnectionString()
	{
		$username = urlencode($this->username);
		$password = urlencode($this->password);
		$database = urlencode($this->database);
		$host = urlencode($this->host);

		return "mysql://$username:$password@$host/$database";
	}

	public function createDatabase()
	{
		$knowledge = GlobalKnowledge::getInstance();

		$username_key = '/config/databases/mysql/' . $this->host . '/username';
		if ($knowledge->exists($username_key)) {
			throw new DatabaseFeatureException('Cannot create the database. The root username key at ' . $username_key . ' could not be found.');
		}

		$password_key = '/config/databases/mysql/' . $this->host . '/password';
		if ($knowledge->exists($password_key)) {
			throw new DatabaseFeatureException('Cannot create the database. The root password key at ' . $password_key . ' could not be found.');
		}

		$pdo = new \PDO("mysql:host={$this->host}", $knowledge->get($username_key), $knowledge->get($password_key));
		$pdo->exec("CREATE DATABASE `{$this->database}`;");
		$pdo->exec("GRANT ALL ON `{$this->database}`.* to '{$this->username}'@'{$this->host}' IDENTIFIED BY '{$this->password}';");

		$this->command->log('Created database successfully.', 'white', BaseCommand::DEBUG_VERBOSE);
	}

}
