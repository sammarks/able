<?php

namespace Able\Helpers;

use Able\Helpers\GlobalKnowledge\GlobalKnowledge;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;
use Symfony\Component\Yaml\Yaml;

class ConfigurationManager {

	use SingletonTrait;

	/**
	 * The loaded configuration.
	 * @var array
	 */
	protected $config = array();

	/**
	 * The locations to grab the configuration from.
	 * @var array
	 */
	protected $locations = array();

	protected function __construct()
	{
		$this->locations = $this->defaultLocations();
		$this->refreshConfig();
	}

	/**
	 * Add Config Location
	 *
	 * Adds a configuration location to the list of files to grab configuration from.
	 * Configuration locations are loaded in order, so later locations override values
	 * from earlier locations.
	 *
	 * @param string $location The path to the file.
	 */
	public function addConfigLocation($location)
	{
		$this->locations[] = $location;
		$this->refreshConfig();
	}

	/**
	 * Get
	 *
	 * Gets a configuration value.
	 *
	 * @param string $key The key of the configuration value. Levels are separated by '/'
	 *
	 * @return array|null Either the value of the key or null if the key could not be found.
	 */
	public function get($key = '')
	{
		$array_keys = explode('/', $key);
		$item = $this->config;
		foreach ($array_keys as $key) {
			if (!is_array($item)) return null;
			if (array_key_exists($key, $item)) {
				$item = $item[$key];
			} else {
				return null;
			}
		}

		return $item;
	}

	/**
	 * Default Locations
	 *
	 * Gets a list of default locations.
	 *
	 * @return array The list of default locations.
	 */
	protected function defaultLocations()
	{
		return array(
			'yaml://' . SCRIPTS_ROOT . '/config/config.yaml',
			'etcd://http://127.0.0.1:4001/config',
			'yaml:///etc/able/config.yaml',
		);
	}

	/**
	 * Refresh Config
	 *
	 * Refreshes the configuration values from the locations.
	 */
	protected function refreshConfig()
	{
		$this->config = array();
		foreach ($this->locations as $location) {
			$config = $this->parseLocation($location);
			if (is_array($config)) {
				$this->config = array_replace_recursive($this->config, $config);
			}
		}
	}

	/**
	 * Parse Location
	 *
	 * Parses a location and returns an array of its configuration values.
	 *
	 * @param string $location The location to parse.
	 *
	 * @return array An array of the configuration values. An empty array if there weren't any.
	 * @throws \Exception
	 */
	protected function parseLocation($location)
	{
		$scheme = $this->getLocationScheme($location);
		$scheme_function = $this->getSchemeFunction($scheme);

		// Get rid of the scheme in the location.
		$location = str_replace($scheme . '://', '', $location);

		if (!$scheme_function) {
			throw new \Exception('There are no scheme functions to handle configurations of type "' . $scheme . '".');
		}

		$configuration = call_user_func_array(array($this, $scheme_function), array($location));
		if (!is_array($configuration)) {
			$configuration = array();
		}

		return $configuration;
	}

	/**
	 * Get Scheme Function
	 *
	 * Gets the scheme function based on the name of the scheme.
	 *
	 * @param string $scheme The name of the scheme.
	 *
	 * @return bool|string The name of the scheme function, or false if none was found.
	 */
	protected function getSchemeFunction($scheme)
	{
		switch ($scheme) {
			case 'yaml':
				return 'parseYamlLocation';
			case 'etcd':
				return 'parseEtcdLocation';
			default:
				return false;
		}
	}

	/**
	 * Get Location Scheme
	 *
	 * Gets the scheme of the specified location.
	 *
	 * @param string $location The location to get the scheme for.
	 *
	 * @return bool|string The scheme of the location, or false on failure.
	 */
	protected function getLocationScheme($location)
	{
		$position = strpos($location, '://');
		if ($position === false) {
			return false;
		} else {
			return substr($location, 0, $position);
		}
	}

	/**
	 * Parse Yaml Location
	 *
	 * @param string $location The path to the Yaml file to parse.
	 *
	 * @return array The configuration loaded from the file.
	 */
	protected function parseYamlLocation($location)
	{
		if (!file_exists($location)) {
			return array();
		}
		$contents = file_get_contents($location);
		if ($contents) {
			return Yaml::parse($contents);
		} else {
			return array();
		}
	}

	/**
	 * Parse Etcd Location
	 *
	 * @param string $location The connection string used to connect to the etcd server.
	 *
	 * @return array The configuration array returned from etcd.
	 */
	protected function parseEtcdLocation($location)
	{
		$etcd_url = parse_url($location, PHP_URL_SCHEME) .
			parse_url($location, PHP_URL_HOST) .
			parse_url($location, PHP_URL_PORT);
		$etcd_path = parse_url($location, PHP_URL_PATH);

		try {

			$configuration = array();
			$etcd = GlobalKnowledge::getInstance($etcd_url);
			$directory_contents = $etcd->listDir($etcd_path, true);

			if (array_key_exists('node', $directory_contents) &&
				!empty($directory_contents['node']['nodes']) &&
				is_array($directory_contents['node']['nodes'])
			) {
				$this->handleEtcdDirectory($configuration, $directory_contents['node']['nodes']);
			}
			return $configuration;

		} catch (\Exception $ex) {
			return array();
		}
	}

	/**
	 * Handle Etcd Directory
	 *
	 * Handles an etcd directory, looking for configuration values.
	 *
	 * @param array $configuration The current configuration array.
	 * @param array $nodes The current list of nodes returned from the list function.
	 */
	protected function handleEtcdDirectory(array &$configuration, array $nodes)
	{
		foreach ($nodes as $node) {
			$key = $node['key'];
			if (array_key_exists('dir', $node) && $node['dir'] && !empty($node['nodes']) && is_array($node['nodes'])) {
				$configuration[$key] = array();
				$this->handleEtcdDirectory($configuration[$key], $node['nodes']);
			} else if (array_key_exists('value', $node)) {
				$configuration[$key] = $node['value'];
			}
		}
	}

} 
