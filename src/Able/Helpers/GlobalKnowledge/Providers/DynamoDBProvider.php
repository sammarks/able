<?php

namespace Able\Helpers\GlobalKnowledge\Providers;

use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\ConfigurationManager;
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
		Logger::getInstance()->log('Connecting to Amazon DynamoDB.', 'white', Logger::DEBUG_VERBOSE);
		$this->client = DynamoDbClient::factory(array(
			'key' => ConfigurationManager::getInstance()->get('aws/access_key'),
			'secret' => ConfigurationManager::getInstance()->get('aws/access_secret'),
			'region' => $this->settings['region'],
		));

		if (!$this->client) {
			Logger::getInstance()->error('Connection to DynamoDB failed. Check your credentials.', true);
		}
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
