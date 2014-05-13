<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

interface ComponentInterface {

	public function initialize(BaseCommand $command, array $settings = array());

}

abstract class Component implements ComponentInterface {

	/**
	 * @var BaseCommand
	 */
	protected $command = null;
	protected $settings = array();

	/**
	 * @var ComponentFactory
	 */
	protected $factory = null;

	public function __construct(ComponentFactory $factory)
	{
		$this->factory = $factory;
	}

	public function initialize(BaseCommand $command, array $settings = array())
	{
		$this->command = $command;
		$this->settings = $settings;

		// Call the post initialize hook, used in some classes.
		$this->postInitialize();
	}

	protected function postInitialize() {}

	public function getClassName()
	{
		$reflect = new \ReflectionClass($this);
		$class_name = $reflect->getShortName();
		$suffix = $this->factory->getComponentClassSuffix();
		return str_replace($suffix, '', $class_name);
	}

}
