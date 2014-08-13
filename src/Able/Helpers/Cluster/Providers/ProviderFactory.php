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
	 * @param string  $type    The type of provider.
	 * @param Cluster $cluster The cluster.
	 *
	 * @return Provider The loaded provider.
	 * @throws \Exception
	 */
	public function provider($type, Cluster $cluster)
	{
		$component = $this->factory($type, null, array());
		if (!($component instanceof Provider)) {
			throw new \Exception('The returned item is not an instance of the Provider class.');
		}

		// Set the settings of the provider to the provider-specific settings.
		if (array_key_exists($component->getClassName(), $cluster->config->get())) {
			$component->setSettings($cluster->config->get($component->getClassName()));
		}

		return $component;
	}

}
