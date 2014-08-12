<?php

namespace Able\Helpers\Cluster\Operations;

use Able\Helpers\Cluster\ClusterConfigurationManager;
use Able\Helpers\Component;

abstract class Operation extends Component {

	/**
	 * The configuration for the cluster.
	 * @var ClusterConfigurationManager
	 */
	protected $config = null;

	/**
	 * Setup Configuration
	 *
	 * Creates the configuration object for the current cluster.
	 *
	 * @param string $name The name of the cluster.
	 *
	 * @throws \Exception
	 */
	public function setupConfiguration($name)
	{
		$this->config = ClusterConfigurationManager::getInstance($name);
		if (!($this->config instanceof ClusterConfigurationManager)) {
			throw new \Exception('There was an error loading the configuration for the cluster.');
		}

		$this->config->setConfiguration($this->settings);
	}

}
