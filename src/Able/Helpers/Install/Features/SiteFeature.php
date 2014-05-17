<?php

namespace Able\Helpers\Install\Features;

use Able\Helpers\Install\ConfigurationManagers\ConfigurationManager;

class SiteFeature extends Feature {

	public function getWeight(ConfigurationManager $config)
	{
		// Make sure the site configuration comes after everything.
		return 100;
	}

	public function getFolder(ConfigurationManager $config)
	{
		// Return the configuration directory relative to the site's repository.
		return $this->settings['repository_root'] . 'config/';
	}

}
