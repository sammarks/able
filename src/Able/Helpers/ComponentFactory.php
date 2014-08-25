<?php

namespace Able\Helpers;

use Able\CommandSets\BaseCommand;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;

interface ComponentFactoryInterface {

	static function getInstance();
	function factory($type, array $settings = array());
	function getComponentClass();
	function getComponentClassSuffix();
	function getInternalPrefix();

}

abstract class ComponentFactory implements ComponentFactoryInterface {

	use SingletonTrait;

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

		return new $accepted_candidate($this);
	}

	public function factory($type, array $settings = array())
	{
		$component = $this->getComponent($type);
		if (!($component instanceof ComponentInterface)) {
			throw new ComponentFactoryException('The ' . $this->getComponentClass() . ' ' . $type . ' is an invalid component.');
		}
		$component->initialize($settings);

		return $component;
	}

}

class ComponentFactoryException extends \Exception {}
