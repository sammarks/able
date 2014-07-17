<?php

namespace Able\Helpers;

use Able\CommandSets\BaseCommand;

interface ComponentFactoryInterface {

	static function getInstance();
	function factory($type, BaseCommand $command, array $settings = array());
	function getComponentClass();
	function getComponentClassSuffix();
	function getInternalPrefix();

}

abstract class ComponentFactory implements ComponentFactoryInterface {

	/**
	 * @var ComponentFactory
	 */
	private static $instance = null;

	public static function getInstance()
	{
		$class_name = get_called_class();
		if (!\Able\Helpers\self::$instance)
			\Able\Helpers\self::$instance = new $class_name();

		return \Able\Helpers\self::$instance;
	}

	protected function getComponent($type)
	{
		$candidates = array();
		$candidates[] = $this->getInternalPrefix() . $type . $this->getComponentClassSuffix();
		$candidates[] = $type;
		$candidates[] = $type . $this->getComponentClassSuffix();

		$candidates = array_reverse($candidates);

		$accepted_candidate = null;
		foreach ($candidates as $candidate) {
			if (class_exists($candidate)) {
				$accepted_candidate = $candidate;
				break;
			}
		}

		if ($accepted_candidate === null) {
			throw new ComponentFactoryException('The ' . $this->getComponentClass() . ' ' . $type . ' does not exist.');
		}

		$reflect = new \ReflectionClass($accepted_candidate);
		if (!$reflect->isSubclassOf($this->getComponentClass())) {
			throw new ComponentFactoryException('The ' . $this->getComponentClass() . ' ' . $type . ' does not extend the ' . $this->getComponentClass() . ' class.');
		}

		$instance = forward_static_call(array(get_called_class(), 'getInstance'));

		return new $accepted_candidate($instance);
	}

	public function factory($type, BaseCommand $command, array $settings = array())
	{
		$component = $this->getComponent($type);
		if (!($component instanceof ComponentInterface)) {
			throw new ComponentFactoryException('The ' . $this->getComponentClass() . ' ' . $type . ' is an invalid component.');
		}
		$component->initialize($command, $settings);

		return $component;
	}

}

class ComponentFactoryException extends \Exception {}
