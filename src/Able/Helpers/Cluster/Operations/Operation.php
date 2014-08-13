<?php

namespace Able\Helpers\Cluster\Operations;

use Able\Helpers\Cluster\Cluster;
use Able\Helpers\Cluster\ClusterConfigurationManager;
use Able\Helpers\Component;

abstract class Operation extends Component {

	/**
	 * The cluster object.
	 * @var Cluster|null
	 */
	protected $cluster = null;

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
		$config = ClusterConfigurationManager::getInstance($name);
		if (!($config instanceof ClusterConfigurationManager)) {
			throw new \Exception('There was an error loading the configuration for the cluster.');
		}

		// Set the configuration and create the cluster based on that.
		$config->setConfiguration($this->settings);
		$this->cluster = new Cluster($config);
	}

}
