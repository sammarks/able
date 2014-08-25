<?php

namespace Able\Helpers\Install\ConfigurationManagers;

use Able\CommandSets\BaseCommand;
use Able\Helpers\ComponentFactory;

class ConfigurationManagerFactory extends ComponentFactory {

	public function getComponentClass()
	{
		return 'Able\\Helpers\\Install\\ConfigurationManagers\\ConfigurationManager';
	}

	public function getComponentClassSuffix()
	{
		return 'ConfigurationManager';
	}

	public function getInternalPrefix()
	{
		return 'Able\\Helpers\\Install\\ConfigurationManagers\\';
	}

	/**
	 * Gets a configuration manager.
	 *
	 * @param string $type     The type of configuration manager.
	 * @param array  $settings The settings array for the site.
	 *
	 * @internal param \Able\CommandSets\BaseCommand $command The command being run.
	 * @return mixed
	 */
	public function manager($type, array $settings = array())
	{
		return $this->factory($type, $settings);
	}

}
