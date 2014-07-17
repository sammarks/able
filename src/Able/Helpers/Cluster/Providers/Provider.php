<?php

namespace Able\Helpers\Cluster\Providers;

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

	public abstract function createNode();

	public abstract function getMetadata();

} 
