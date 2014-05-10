<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

interface InstallerFactory {

	public static function installer($type, BaseCommand $command, array $settings = array());

}

class SiteInstallerFactory implements InstallerFactory {

	protected static function getInstaller($type)
	{
		$class_name = $type . 'SiteInstaller';
		$able_class_name = 'Able\\Helpers\\Install\\' . $class_name;
		if (class_exists($able_class_name)) {
			$class_name = $able_class_name;
		} elseif (!class_exists($class_name)) {
			throw new InstallerFactoryException('The installer ' . $class_name . ' does not exist.');
		}
		return new $class_name();
	}

	public static function installer($type, BaseCommand $command, array $settings = array())
	{
		$installer = self::getInstaller($type);
		$installer->initialize($command, $settings);
		return $installer;
	}

} 

class InstallerFactoryException extends \Exception {}
