<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

interface ComponentFactoryInterface {

	public static function component($type, BaseCommand $command, array $settings = array());

	public static function getComponentClass();
	public static function getComponentClassSuffix();
	public static function getInternalPrefix();

}

abstract class ComponentFactory implements ComponentFactoryInterface {

	protected static function getComponent($type)
	{
		$candidates = array();
		$candidates[] = self::getInternalPrefix() . $type . self::getComponentClassSuffix();
		$candidates[] = $type;
		$candidates[] = $type . self::getComponentClassSuffix();

		$candidates = array_reverse($candidates);

		$accepted_candidate = null;
		foreach ($candidates as $candidate) {
			if (class_exists($candidate)) {
				$accepted_candidate = $candidate;
				break;
			}
		}

		if ($accepted_candidate === null) {
			throw new ComponentFactoryException('The ' . self::getComponentClass() . ' ' . $type . ' does not exist.');
		}

		$reflect = new \ReflectionClass($accepted_candidate);
		if (!$reflect->isSubclassOf(self::getComponentClass())) {
			throw new ComponentFactoryException('The ' . self::getComponentClass() . ' ' . $type . ' does not extend the ' . self::getComponentClass() . ' class.');
		}

		return new $accepted_candidate();
	}

	public static function component($type, BaseCommand $command, array $settings = array())
	{
		$component = self::getComponent($type);
		if (!($component instanceof ComponentInterface)) {
			throw new ComponentFactoryException('The ' . self::getComponentClass() . ' ' . $type . ' is an invalid component.');
		}
		$component->initialize($command, $settings);
		return $component;
	}

}

class ComponentFactoryException extends \Exception {}
