<?php

namespace Able\Helpers\Install\Features;

use Able\Helpers\Install\Component;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManager;

abstract class Feature extends Component {

	protected $configuration = array();

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
	}

	public function preCopy($directory) {}
	public function postCopy($directory) {}
	public function postRestartServices() {}
	public function alterWebroot($directory) {}

}
