<?php

namespace Able\Helpers\Install;

class BasicSiteInstaller extends SiteInstaller {

	public function install()
	{

	}

	protected function prepareVHost()
	{
		// Create the new vhost class.
		$vhost = new VHostConfigManager($this->command, $this->settings);

		// Add all features specified in the configuration.

	}

}
