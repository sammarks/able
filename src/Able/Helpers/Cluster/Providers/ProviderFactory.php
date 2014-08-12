<?php

namespace Able\Helpers\Cluster\Providers;

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
	 * @param string $type       The type of provider.
	 * @param string $identifier The identifier for the node.
	 * @param array  $settings   An array of settings for the node.
	 *
	 * @return Provider The loaded provider.
	 * @throws \Exception
	 */
	public function provider($type, $identifier = '', array $settings = array())
	{
		$provider_settings = $settings[$type];

		$component = $this->factory($type, null, $provider_settings);
		if (!($component instanceof Provider)) {
			throw new \Exception('The returned item is not an instance of the Provider class.');
		}

		$component->setIdentifier($identifier);
		$component->setNodeSettings($settings);

		return $component;
	}

	/**
	 * All
	 *
	 * @return array An array of all the providers.
	 */
	public function all()
	{
		$providers = array('EC2');
		$provider_instances = array();
		foreach ($providers as $provider) {
			$provider_instances[] = $this->factory($provider, null, array());
		}

		return $provider_instances;
	}

}
