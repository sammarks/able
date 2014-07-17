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
		return 'Able\\Helpers\\Install\\ConfigurationManagers';
	}

	/**
	 * Gets a configuration manager.
	 *
	 * @param string      $type     The type of configuration manager.
	 * @param BaseCommand $command  The command being run.
	 * @param array       $settings The settings array for the site.
	 *
	 * @return mixed
	 */
	public function manager($type, BaseCommand $command, array $settings = array())
	{
		return $this->factory($type, $command, $settings);
	}

}
