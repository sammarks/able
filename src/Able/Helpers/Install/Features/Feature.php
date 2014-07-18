<?php

namespace Able\Helpers\Install\Features;

use Able\Helpers\Component;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManager;

abstract class Feature extends Component {

	protected $configuration = array();

	/**
	 * @var FeatureCollection
	 */
	public $feature_collection = null;

	public function getWeight(ConfigurationManager $config)
	{
		$class_name = $config->getClassName();
		if ($class_name == 'VHost') return 1;
		return 0;
	}

	public function getFolder(ConfigurationManager $config)
	{
		return $this->getClassName() . '/' . $config->getClassName();
	}

	public function getDependencies()
	{
		return array();
	}

	public function getConfigurationArray(ConfigurationManager $config)
	{
		return array();
	}

	public function setConfiguration(array $configuration = array())
	{
		$this->configuration = $configuration;

		// Call the post set configuration hook.
		$this->postSetConfiguration();
	}

	public function preCopy($directory) {}
	public function postCopy($directory) {}
	public function postRestartServices() {}
	public function alterWebroot($directory) {}
	public function postSetConfiguration() {}

}
