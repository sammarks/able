<?php

namespace Able\Helpers\Install\Features;

use Able\CommandSets\BaseCommand;

class FeatureCollection extends \ArrayObject {

	protected $added_features = array();
	protected $base_command = null;
	protected $settings = null;

	public function initialize(BaseCommand $base_command, array $settings = array())
	{
		$this->base_command = $base_command;
		$this->settings = $settings;
	}

	public function offsetSet($index, $newval)
	{
		// Make sure we're only getting features...
		if (!($newval instanceof Feature)) {
			throw new FeatureCollectionException('The value being added to the collection is not a feature.');
		}

		$class_name = $newval->getClassName();
		if (array_search($class_name, $this->added_features) !== false) {
			return; // Don't add the feature.
		}

		// Resolve the dependencies of the feature.
		$this->resolveDependencies($newval);

		// Set the configuration for the feature if it exists.
		if (!empty($this->settings['features'][$class_name])) {
			$newval->setConfiguration($this->settings['features'][$class_name]);
		}

		$this->added_features[$index] = $class_name;

		parent::offsetSet($index, $newval);
	}

	public function featureExists($feature)
	{
		if (is_string($feature)) {
			$feature_name = $feature;
		} elseif (is_object($feature) && $feature instanceof Feature) {
			$feature_name = $feature->getClassName();
		} else {
			return false;
		}

		if (($index = array_search($feature_name, $this->added_features)) !== false) {
			return $index;
		}

		return false;
	}

	protected function resolveDependencies(Feature $feature)
	{
		if ($this->base_command === null || $this->settings === null) {
			throw new FeatureCollectionException('The feature collection has not yet been initialized.');
		}

		foreach ($feature->getDependencies() as $dependency) {
			if (!$this->featureExists($dependency)) {
				$this[] = FeatureFactory::getInstance()->factory($dependency, $this->base_command, $this->settings);
			}
		}
	}

	public function callHook($hook)
	{
		$args = func_get_args();
		array_shift($args); // Remove $hook.

		$results = array();
		foreach ($this as $feature) {
			if (method_exists($feature, $hook)) {
				$result = call_user_func_array(array($feature, $hook), $args);
				if ($result === null) continue;
				$results[] = $result;
			}
		}

		if (count($results) === 1) {
			return $results[0];
		} elseif (count($results) === 0) {
			return null;
		} else {
			return $results;
		}
	}

	public function alterHook($hook, $default_value = null)
	{
		$args = func_get_args();
		unset($args[1]); // Remove $default_value

		// Reset the array indexes.
		$args = array_values($args);

		$results = call_user_func_array(array($this, 'callHook'), $args);
		if (is_array($results)) {
			throw new FeatureCollectionException('More than one alter was performed. This most likely means two features are in conflict.');
		}
		if ($results === null) {
			return $default_value;
		}
		return $results;
	}

}

class FeatureCollectionException extends \Exception {}
