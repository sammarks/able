<?php

namespace Able\Helpers\Cluster;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Cluster\Providers\Provider;
use Able\Helpers\Cluster\Providers\ProviderFactory;
use Able\Helpers\CommandHelpers\Logger;

class Cluster {

	/**
	 * The name of the cluster.
	 * @var string
	 */
	protected $name = '';

	/**
	 * An array of nodes found in the cluster.
	 * @var array
	 */
	protected $nodes = array();

	/**
	 * The configuration for the cluster.
	 * @var ClusterConfigurationManager|null
	 */
	public $config = null;

	public function __construct(ClusterConfigurationManager $config)
	{
		$this->name = $config->name;
		$this->config = $config;
		$this->refreshNodes();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getNodes()
	{
		return $this->nodes;
	}

	/**
	 * @return int The number of nodes currently in the cluster.
	 */
	public function nodeCount()
	{
		return count($this->nodes);
	}

	/**
	 * Refresh Nodes
	 *
	 * Re-generates the $nodes variable by calling functions on each of the providers.
	 */
	protected function refreshNodes()
	{
		Logger::getInstance()->log('Refreshing nodes for cluster ' . $this->getName() . '.', 'white', Logger::DEBUG_VERBOSE);

		// Get a simple list of all the providers for the cluster.
		$providers = array();
		foreach ($this->config->get('nodes') as $node) {
			$providers[$node['provider']] = $node;
		}

		/** @var ProviderFactory $factory */
		$factory = ProviderFactory::getInstance();
		foreach ($providers as $provider => $settings) {
			/** @var Provider $provider */
			$provider = $factory->provider($provider, $this, $settings);
			$nodes = $provider->getNodes();
			if ($nodes && is_array($nodes)) {
				foreach ($nodes as $node) {
					$this->nodes[] = $node;
				}
			}
		}
	}

} 
