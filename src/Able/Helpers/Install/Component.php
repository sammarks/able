<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

interface ComponentInterface {

	public function initialize(BaseCommand $command, array $settings = array());

}

abstract class Component implements ComponentInterface {

	protected $command = null;
	protected $settings = array();

	public function initialize(BaseCommand $command, array $settings = array())
	{
		$this->command = $command;
		$this->settings = $settings;
	}

}
