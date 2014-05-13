<?php

namespace Able\Helpers\Install\Installers;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Install\ComponentFactory;

class InstallerFactory extends ComponentFactory {

	public function getComponentClass()
	{
		return 'Able\\Helpers\\Install\\Installers\\Installer';
	}

	public function getComponentClassSuffix()
	{
		return 'Installer';
	}

	public function getInternalPrefix()
	{
		return 'Able\\Helpers\\Install\\Installers\\';
	}

	/**
	 * Gets an installer.
	 *
	 * @param string      $type     The type of installer.
	 * @param BaseCommand $command  The command being run.
	 * @param array       $settings The settings array for the site.
	 *
	 * @return Installer
	 */
	public function installer($type, BaseCommand $command, array $settings = array())
	{
		return $this->factory($type, $command, $settings);
	}

}
