<?php

namespace Able\Helpers\GlobalKnowledge;

use Able\Helpers\ConfigurationManager;
use Able\Helpers\GlobalKnowledge\Providers\Provider;
use Able\Helpers\GlobalKnowledge\Providers\ProviderFactory;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;

class GlobalKnowledge {

	use SingletonTrait;

	/**
	 * The global knowledge provider.
	 * @var Provider
	 */
	protected $provider = null;

	public function construct()
	{
		/** @var ConfigurationManager $config */
		$config = ConfigurationManager::getInstance();
		$provider = $config->get('global_knowledge/provider');
		$provider_settings = $config->get('global_knowledge/' . $provider);

		/** @var ProviderFactory $provider_factory */
		$provider_factory = ProviderFactory::getInstance();
		$this->provider = $provider_factory->factory($provider, $provider_settings);
		if (!($this->provider instanceof Provider))
			throw new \Exception('An invalid provider was specified.');

		// Connect to the provider.
		$this->provider->connect();
	}

}
