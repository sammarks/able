<?php

namespace Able\Helpers\Cluster;

use Able\Helpers\ConfigurationManager;

class ClusterConfigurationManager extends ConfigurationManager {

	public $name = '';

	protected function __construct($name)
	{
		parent::__construct();

		$this->name = $name;
	}

	protected function defaultLocations()
	{
		return array(
			'yaml://' . SCRIPTS_ROOT . '/config/cluster.yaml',
		);
	}

	public function setConfiguration(array $configuration = array())
	{
		if (!array_key_exists('cluster-defaults', $this->config)) {
			throw new \Exception('The default cluster configuration could not be found.');
		}

		$this->config = array_replace_recursive($this->config['cluster-defaults'], $configuration);
	}

} 
