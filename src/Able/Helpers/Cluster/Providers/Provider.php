<?php

namespace Able\Helpers\Cluster\Providers;

use Able\Helpers\Cluster\Cluster;
use Able\Helpers\Cluster\Node;
use Able\Helpers\Component;

abstract class Provider extends Component {

	protected $identifier = '';
	protected $node_settings = array();

	public function setIdentifier($identifier)
	{
		$this->identifier = $identifier;
	}

	public function setNodeSettings(array $settings)
	{
		$this->node_settings = $settings;
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
	public abstract function createNode();

	/**
	 * Get Metadata
	 *
	 * Gets the default provider-specific metadata.
	 *
	 * @return array
	 */
	public abstract function getMetadata();

	/**
	 * Get Nodes
	 *
	 * @param Cluster $cluster The cluster to get the nodes for.
	 *
	 * @return array An array of nodes for the specified cluster.
	 */
	public abstract function getNodes(Cluster $cluster);

	/**
	 * Inspect Node
	 *
	 * @param Cluster $cluster         The cluster to get the node from.
	 * @param string  $node_identifier A provider-specific identifier for the node.
	 *
	 * @return \Able\Helpers\Cluster\Node|bool A node object representing the specified node, or false.
	 * @throws \Exception
	 */
	public abstract function inspectNode(Cluster $cluster, $node_identifier);

	/**
	 * Find Cluster
	 *
	 * @param string $cluster_identifier The identifier for the cluster to search for.
	 *
	 * @return array|bool An array of instance IDs if there are nodes in the cluster, false if not.
	 */
	public abstract function findCluster($cluster_identifier);

	/**
	 * Get Node Private IP
	 *
	 * @param Node $node The node to get the private IP for.
	 *
	 * @return string The private IP address for the node.
	 */
	public abstract function getNodePrivateIp(Node $node);

} 
