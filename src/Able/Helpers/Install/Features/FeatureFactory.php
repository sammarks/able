<?php

namespace Able\Helpers\Install\Features;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Install\ComponentFactory;

class FeatureFactory extends ComponentFactory {

	public static function getComponentClass()
	{
		return 'Able\\Helpers\\Install\\Features\\Feature';
	}

	public static function getComponentClassSuffix()
	{
		return 'Feature';
	}

	public static function getInternalPrefix()
	{
		return 'Able\\Helpers\\Install\\Features\\';
	}

	/**
	 * Gets a feature.
	 *
	 * @param string      $type     The type of feature.
	 * @param BaseCommand $command  The command being run.
	 * @param array       $settings The settings array for the site.
	 *
	 * @return Feature
	 */
	public static function feature($type, BaseCommand $command, array $settings = array())
	{
		return self::component($type, $command, $settings);
	}

}
