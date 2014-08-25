<?php

namespace Able\Helpers\Install\ConfigurationManagers;

use Able\Helpers\IniWriter;
use Able\Helpers\Install\Features\Feature;
use Able\Helpers\CommandHelpers\Logger;

abstract class IniConfigurationManager extends ConfigurationManager {

	protected $configuration = array();

	/**
	 * Has Sections
	 *
	 * @return bool Whether or not the current ini file has sections.
	 */
	protected function hasSections()
	{
		return true;
	}

	/**
	 * String Values
	 *
	 * @return bool Whether or not we should use quotes around the values of the configuration.
	 */
	protected function stringValues()
	{
		return false;
	}

	public function postInitialize()
	{
		parent::postInitialize();

		if ($this->existing_contents) {
			$this->configuration = parse_ini_string($this->existing_contents, true);
		} else {
			$this->configuration = array();
		}

		// Get the initial configuration values from the site settings.
		if (array_key_exists($this->getClassName(), $this->settings['configuration']) &&
			is_array($this->settings['configuration'][$this->getClassName()])) {
			$site_configuration = $this->settings['configuration'][$this->getClassName()];
			$this->configuration = array_replace_recursive($this->configuration, $site_configuration);
		}
	}

	public function set($key, $value)
	{
		$this->configuration[$key] = $value;
	}

	public function get($key)
	{
		return $this->configuration[$key];
	}

	protected function handleFeature(Feature $feature)
	{
		$config = $feature->getConfigurationArray($this);
		$this->configuration = array_replace_recursive($this->configuration, $config);
	}

	public function build()
	{
		foreach ($this->features as $feature) {
			$this->handleFeature($feature);
		}

		return IniWriter::getInstance($this->hasSections(), $this->stringValues())->write($this->configuration);
	}

}
