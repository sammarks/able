<?php

namespace Able\Helpers\Cluster;

use Able\Helpers\Cluster\Providers\ProviderFactory;

class Node {

	/**
	 * The name of the node.
	 * @var string
	 */
	protected $name;

	/**
	 * The cluster the node belongs to.
	 * @var Cluster|null
	 */
	protected $cluster = null;

	/**
	 * The internal provider-specified identifier for the node.
	 * @var string
	 */
	protected $id;

	/**
	 * The name of the provider the node is currently on.
	 * @var string
	 */
	protected $provider;

	/**
	 * The provider-specific attributes for the node.
	 * @var string
	 */
	protected $attributes;

	public function __construct($name, Cluster $cluster, $provider, $id, $attributes = array())
	{
		$this->name = $name;
		$this->cluster = $cluster;
		$this->provider = $provider;
		$this->id = $id;
		$this->attributes = $attributes;
	}

	/**
	 * Get Internal IP Address
	 *
	 * @return string Gets the internal IP address of the node.
	 */
	public function getInternalIpAddress()
	{
		/** @var ProviderFactory $factory */
		$factory = ProviderFactory::getInstance();

		$provider = $factory->provider($this->provider, $this->name);
		return $provider->getNodePrivateIp($this);
	}

	/**
	 * @return string
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * @return \Able\Helpers\Cluster\Cluster|null
	 */
	public function getCluster()
	{
		return $this->cluster;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getProvider()
	{
		return $this->provider;
	}

}
