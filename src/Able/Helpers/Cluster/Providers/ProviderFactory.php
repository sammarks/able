<?php

namespace Able\Helpers\Cluster\Providers;

use Able\Helpers\Cluster\Cluster;
use Able\Helpers\ComponentFactory;

class ProviderFactory extends ComponentFactory {

	function getComponentClass()
	{
		return 'Able\\Helpers\\Cluster\\Providers\\Provider';
	}

	function getComponentClassSuffix()
	{
		return 'Provider';
	}

	function getInternalPrefix()
	{
		return 'Able\\Helpers\\Cluster\\Providers\\';
	}

	/**
	 * Provider
	 *
	 * Gets a provider.
	 *
	 * @param string  $type     The type of provider.
	 * @param Cluster $cluster  The cluster.
	 * @param array   $settings The provider-specific settings for the current node.
	 *
	 * @return Provider The loaded provider.
	 * @throws \Exception
	 */
	public function provider($type, Cluster $cluster, array $settings = array())
	{
		$component = $this->factory($type, null, array());
		if (!($component instanceof Provider)) {
			throw new \Exception('The returned item is not an instance of the Provider class.');
		}

		// Set the settings of the provider to the provider-specific settings.
		if (array_key_exists($component->getClassName(), $settings)) {
			$component->setSettings($settings[$component->getClassName()]);
		}

		// Set the cluster.
		$component->setCluster($cluster);

		return $component;
	}

}
