<?php

namespace Able\Helpers\Install\ConfigurationManagers;

use Able\Helpers\Install\Features\Feature;
use Able\Helpers\Logger;

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

		return $this->write_ini_file($this->configuration, true);
	}

	// From: http://stackoverflow.com/questions/1268378/create-ini-file-write-values-in-php
	protected function write_ini_file($assoc_arr)
	{
		$content = "";
		if ($this->hasSections()) {
			foreach ($assoc_arr as $key => $elem) {
				if (!is_array($elem)) {
					/** @var \Able\Helpers\Logger $logger */
					$logger = Logger::getInstance();
					$logger->error('The element: ' . $elem . ' is invalid and has been skipped.');
					continue;
				}
				$content .= "[" . $key . "]\n";
				foreach ($elem as $key2 => $elem2) {
					if (is_array($elem2)) {
						for ($i = 0; $i < count($elem2); $i++) {
							$content .= $key2 . "[] = " . $this->iniValue($elem2[$i]) . "\n";
						}
					} else if ($elem2 === "") $content .= $key2 . " = \n";
					else $content .= $key2 . " = " . $this->iniValue($elem2) . "\n";
				}
			}
		} else {
			foreach ($assoc_arr as $key => $elem) {
				if (is_array($elem)) {
					for ($i = 0; $i < count($elem); $i++) {
						$content .= $key . "[] = " . $this->iniValue($elem[$i]) . "\n";
					}
				} else if ($elem === "") $content .= $key . " = \n";
				else $content .= $key . " = " . $this->iniValue($elem) . "\n";
			}
		}

		return $content;
	}

	protected function iniValue($value)
	{
		if ($this->stringValues()) {
			if (is_numeric($value)) {
				return $value;
			} else {
				return "\"{$value}\"";
			}
		} else return $value;
	}

	protected function verifyIniSections($arr)
	{
		foreach ($arr as $item) {
			if (!is_array($item)) return false;
		}
		return true;
	}

}
