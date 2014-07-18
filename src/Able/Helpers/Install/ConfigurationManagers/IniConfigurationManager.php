<?php

namespace Able\Helpers\Install\ConfigurationManagers;

use Able\Helpers\Install\Features\Feature;

abstract class IniConfigurationManager extends ConfigurationManager {

	protected $configuration = array();

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

		return $this->arr2ini($this->configuration);
	}

	// From: http://stackoverflow.com/questions/17316873/php-array-to-a-ini-file
	protected function arr2ini(array $a, array $parent = array())
	{
		$out = '';
		foreach ($a as $k => $v) {
			if (is_array($v)) {
				//subsection case
				//merge all the sections into one array...
				$sec = array_merge((array)$parent, (array)$k);
				//add section information to the output
				$out .= '[' . join('.', $sec) . ']' . PHP_EOL;
				//recursively traverse deeper
				$out .= $this->arr2ini($v, $sec);
			} else {
				//plain key->value case
				$out .= "$k=$v" . PHP_EOL;
			}
		}

		return $out;
	}

}
