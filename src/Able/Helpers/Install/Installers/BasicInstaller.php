<?php

namespace Able\Helpers\Install\Installers;

use Able\Helpers\Install\VHostConfigManager;
use Able\Helpers\Install\Features\FeatureFactory;

class BasicInstaller extends Installer {

	public function install()
	{

	}

	protected function prepareVHost()
	{
		// Create the new vhost class.
		$vhost = new VHostConfigManager($this->command, $this->settings);

		// Add all features specified in the configuration.
		if (array_key_exists('features', $this->settings) && is_array($this->settings['features'])) {
			foreach ($this->settings['features'] as $feature) {
				$vhost->addFeature(FeatureFactory::feature($feature, $this->command, $this->settings));
			}
		}
	}

}
