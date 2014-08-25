<?php

namespace Able\Helpers\GlobalKnowledge\Providers;

use Able\Helpers\Component;

abstract class Provider extends Component {

	/**
	 * Connect
	 *
	 * @throws \Exception If the connection operation was not successful, an exception is thrown.
	 */
	public abstract function connect();

	/**
	 * Get
	 *
	 * @param string $path The path of the value to get. Use '/' as a separator. If an empty string
	 *                     is passed, the entire configuration object is returned.
	 *
	 * @return mixed The value of the key.
	 */
	public abstract function get($path = '');

	/**
	 * Set
	 *
	 * @param string $path  The path of the value to set. Use '/' as a separator. If an empty string
	 *                      is passed, nothing is done.
	 * @param mixed  $value The new value of the field. If null is passed, the value is unset.
	 */
	public abstract function set($path = '', $value = null);

} 
