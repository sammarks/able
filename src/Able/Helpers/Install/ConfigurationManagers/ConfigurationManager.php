<?php

namespace Able\Helpers\Install\ConfigurationManagers;

use Able\Helpers\Component;
use Able\Helpers\Install\Features\Feature;
use Able\Helpers\Install\Features\FeatureCollection;

abstract class ConfigurationManager extends Component {

	protected $existing_contents = null;

	/**
	 * @var \Able\Helpers\Install\Features\FeatureCollection
	 */
	protected $features = null;

	public function setFeatureCollection(FeatureCollection $features)
	{
		$this->features = $features;
	}

	public function save()
	{
		$contents = $this->build();

		$filename = $this->getFileLocation();
		if (!file_put_contents($filename, $contents))
			throw new ConfigurationManagerException('There was an error saving the configuration to ' . $filename);
	}

	public function postInitialize()
	{
		// Load the existing file, if one exists.
		$filename = $this->getFileLocation();
		if (file_exists($filename)) {
			if ($contents = file_get_contents($filename)) {
				$this->existing_contents = $contents;
			}
		}
	}

	protected function getFileLocation()
	{
		// Get the filename from the global settings.
		$config_locations = $this->command->config->get('server/configuration');
		$class_name = $this->getClassName();
		if (!array_key_exists($class_name, $config_locations))
			throw new ConfigurationManagerException('There is no config location defined for ' . $class_name);

		// Return the result.
		return $config_locations[$class_name];
	}

	protected function getFeatureConfigurationFolderBase(Feature $feature)
	{
		$feature_folder = $feature->getFolder($this);

		// Make sure the feature folder ends in /
		if (substr($feature_folder, strlen($feature_folder) - 1) != '/') {
			$feature_folder .= '/';
		}

		// Append the name of this configuration class.
		$feature_folder .= $this->getClassName();

		return $feature_folder;
	}

	protected function getFeatureConfigurationFolderLocal(Feature $feature)
	{
		$feature_folder = $this->getFeatureConfigurationFolderBase($feature);

		if (!is_dir($feature_folder)) return false;
		return $feature_folder;
	}

	protected function getFeatureConfigurationFolderGlobal(Feature $feature)
	{
		$feature_folder = SCRIPTS_ROOT . '/lib/features/' . $this->getFeatureConfigurationFolderBase($feature);

		if (!is_dir($feature_folder)) return false;
		return $feature_folder;
	}

	public abstract function build();

	protected function handleFeature(Feature $feature) {}

}

class ConfigurationManagerException extends \Exception {}
