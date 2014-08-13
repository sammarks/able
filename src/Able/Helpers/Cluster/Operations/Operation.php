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

		// Fill in the default cluster values.
		$this->settings = $config->fillDefaults($this->settings);

		// Apply the defaults for the nodes.
		if (!empty($this->settings['nodes']) && is_array($this->settings['nodes'])) {
			foreach ($this->settings['nodes'] as $identifier => $node) {
				if (!is_array($node)) $node = array();
				if (!empty($this->settings['defaults']) && is_array($this->settings['defaults'])) {
					$this->settings['nodes'][$identifier] = array_replace_recursive($this->settings['defaults'], $node);
				}
				$this->settings['nodes'][$identifier]['full-identifier'] = $config->name . '-' . $identifier;
				$this->settings['nodes'][$identifier]['cluster'] = $config->name;
			}
		}

		// Set the configuration and create the cluster based on that.
		$config->setConfiguration($this->settings);
		$this->cluster = new Cluster($config);
	}

}
