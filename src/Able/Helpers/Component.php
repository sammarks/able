<?php

namespace Able\Helpers;

use Able\CommandSets\BaseCommand;
use Able\Helpers\ComponentFactory;

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

	public function initialize(BaseCommand $command = null, array $settings = array())
	{
		$this->command = $command;
		$this->settings = $settings;

		// Call the post initialize hook, used in some classes.
		$this->postInitialize();
	}

	protected function postInitialize()
	{
	}

	public function getClassName()
	{
		$reflect = new \ReflectionClass($this);
		$class_name = $reflect->getShortName();
		$suffix = $this->factory->getComponentClassSuffix();

		return str_replace($suffix, '', $class_name);
	}

	public function getParentNames($class = null)
	{
		if ($class === null) $class = $this;
		return self::getClassParentNames($class, $this->factory);
	}

	public static function getClassParentNames($class, ComponentFactory $factory)
	{
		$reflect = new \ReflectionClass($class);
		$parent_class = $reflect->getParentClass();
		$result = array();
		if ($parent_class) {
			$class_name = $parent_class->getShortName();
			$suffix = $factory->getComponentClassSuffix();
			$result[] = str_replace($suffix, '', $class_name);
			$result = array_merge($result, self::getClassParentNames($parent_class->name, $factory));
		}

		return $result;
	}

}
