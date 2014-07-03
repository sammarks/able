<?php

namespace Able\Helpers\GlobalKnowledge;

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
	 */
	public static function getInstance($url = 'http://127.0.0.1:4001')
	{
		if (!self::$instance) {
			self::$instance = new self($url);
		}

		return self::$instance;
	}

}
