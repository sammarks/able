<?php

namespace Able\Helpers\GlobalKnowledge\Providers;

use Aws\DynamoDb\DynamoDbClient;

class DynamoDBProvider extends Provider {

	/**
	 * The current client connection.
	 * @var DynamoDBClient
	 */
	protected $client = null;

	/**
	 * {@inheritDoc}
	 */
	public function connect()
	{

	}

	/**
	 * {@inheritDoc}
	 */
	public function get($path = '')
	{

	}

	/**
	 * {@inheritDoc}
	 */
	public function set($path = '', $value = null)
	{

	}

}
