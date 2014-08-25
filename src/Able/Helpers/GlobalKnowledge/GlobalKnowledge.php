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
		$this->provider = $provider_factory->factory($provider, null, $provider_settings);
	}

}
