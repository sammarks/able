<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

interface FeatureInterface {

	public function getWeight();
	public function getFolderName();

}

abstract class Feature implements FeatureInterface {

	protected $command = null;
	protected $settings = array();

	public function __construct(BaseCommand $command, array $settings = array())
	{
		$this->command = $command;
		$this->settings = $settings;
	}

	public function getWeight()
	{
		return 0;
	}

	public function getFolderName()
	{
		return get_class($this);
	}

}
