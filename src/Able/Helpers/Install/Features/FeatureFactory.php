<?php

namespace Able\Helpers\Install\Features;

use Able\CommandSets\BaseCommand;
use Able\Helpers\ComponentFactory;

class FeatureFactory extends ComponentFactory {

	public function getComponentClass()
	{
		return 'Able\\Helpers\\Install\\Features\\Feature';
	}

	public function getComponentClassSuffix()
	{
		return 'Feature';
	}

	public function getInternalPrefix()
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
	public function feature($type, BaseCommand $command, array $settings = array())
	{
		return $this->factory($type, $command, $settings);
	}

}
