<?php

namespace Able\Helpers\GlobalKnowledge;

use Able\Helpers\ScopeManager;
use LinkORB\Component\Etcd\Client;

class GlobalKnowledge extends Client {

	/**
	 * @var GlobalKnowledge
	 */
	protected static $instance = null;

	/**
	 * Get Instance
	 *
	 * Gets the current instance of the GlobalKnowledge class, or creates one if it doesn't exist.
	 *
	 * @param string $url The URL used to connect to etcd.
	 *
	 * @return GlobalKnowledge
	 * @throws \Exception
	 */
	public static function getInstance($url = 'http://127.0.0.1:4001')
	{
		if (ScopeManager::getInstance()->getScope() == ScopeManager::SCOPE_NONE) {
			throw new \Exception('The Global Knowledge is only available to nodes in the cluster and containers.');
		}

		if (!self::$instance) {
			self::$instance = new self($url);

			// Make sure the server is alive.
			if (!self::$instance->ping()) {
				throw new \Exception('There was an error connecting to the etcd server at ' . $url);
			}
		}

		return self::$instance;
	}

	/**
	 * Ping
	 *
	 * Tests to see if the server is alive.
	 *
	 * @return bool
	 */
	public function ping()
	{
		try {
			$this->get('/');
			return true;
		} catch (\Exception $ex) {
			return false;
		}
	}

}
