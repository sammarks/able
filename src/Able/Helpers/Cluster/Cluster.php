<?php

namespace Able\Helpers\Cluster;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Cluster\Providers\Provider;
use Able\Helpers\Cluster\Providers\ProviderFactory;
use Able\Helpers\Logger;

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

	public function __construct($name)
	{
		$this->name = $name;
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
		/** @var Logger $logger */
		$logger = Logger::getInstance();
		$logger->log('Refreshing nodes for cluster ' . $this->getName() . '.', 'white', BaseCommand::DEBUG_VERBOSE);

		/** @var ProviderFactory $factory */
		$factory = ProviderFactory::getInstance();
		foreach ($factory->all() as $provider) {
			/** @var Provider $provider */
			$nodes = $provider->getNodes($this);
			if ($nodes && is_array($nodes)) {
				foreach ($nodes as $node) {
					$this->nodes[] = $node;
				}
			}
		}
	}

} 
