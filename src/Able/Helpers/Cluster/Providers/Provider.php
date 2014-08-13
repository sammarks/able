<?php

namespace Able\Helpers\Cluster\Providers;

use Able\Helpers\Cluster\Cluster;
use Able\Helpers\Cluster\Node;
use Able\Helpers\Component;

abstract class Provider extends Component {

	/**
	 * The cluster.
	 * @var Cluster|null
	 */
	protected $cluster = null;

	public function setCluster(Cluster $cluster)
	{
		$this->cluster = $cluster;
	}

	public function setSettings(array $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Create Node
	 *
	 * Creates a node based on the provided settings.
	 */
	public abstract function createNode($identifier, array $node_settings);

	/**
	 * Get Metadata
	 *
	 * Gets the default provider-specific metadata.
	 *
	 * @param string $identifier The identifier for the node.
	 *
	 * @return array
	 */
	public abstract function getMetadata($identifier);

	/**
	 * Get Nodes
	 *
	 * @return array An array of nodes for the loaded cluster.
	 */
	public abstract function getNodes();

	/**
	 * Inspect Node
	 *
	 * @param string  $node_identifier A provider-specific identifier for the node.
	 *
	 * @return \Able\Helpers\Cluster\Node|bool A node object representing the specified node, or false.
	 * @throws \Exception
	 */
	public abstract function inspectNode($node_identifier);

	/**
	 * Get Node Private IP
	 *
	 * @param Node $node The node to get the private IP for.
	 *
	 * @return string The private IP address for the node.
	 */
	public abstract function getNodePrivateIp(Node $node);

} 
