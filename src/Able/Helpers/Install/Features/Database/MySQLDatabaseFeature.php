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
		$username = $this->command->config->get('databases/mysql/' . $this->host . '/username');
		if ($username === null) {
			throw new DatabaseFeatureException('Cannot create the database. The root username key at databases/mysql/' . $this->host . '/username could not be found.');
		}

		$password = $this->command->config->get('databases/mysql/' . $this->host . '/password');
		if ($password === null) {
			throw new DatabaseFeatureException('Cannot create the database. The root password key at databases/mysql/' . $this->host . '/password could not be found.');
		}

		$pdo = new \PDO("mysql:host={$this->host}", $username, $password);
		$pdo->exec("CREATE DATABASE `{$this->database}`;");
		$pdo->exec("GRANT ALL ON `{$this->database}`.* to '{$this->username}'@'{$this->host}' IDENTIFIED BY '{$this->password}';");

		$this->command->log('Created database successfully.', 'white', BaseCommand::DEBUG_VERBOSE);
	}

}
