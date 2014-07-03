<?php

namespace Able\Helpers\Install\Features\Database;

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

	}

}
