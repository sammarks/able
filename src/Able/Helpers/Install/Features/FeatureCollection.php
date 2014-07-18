<?php

namespace Able\Helpers\Install\Features;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Component;

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

		// Add any parents of the class so dependencies can be resolved by parent class.
		$other_candidates = $newval->getParentNames();
		foreach ($other_candidates as $candidate) {
			if (array_search($candidate, $this->added_features) === false) {
				$this->added_features[] = $candidate;
			}
		}

		// Resolve the dependencies of the feature.
		$this->resolveDependencies($newval);

		// Set the configuration for the feature if it exists.
		if (!empty($this->settings['features'][$class_name])) {
			$newval->setConfiguration($this->settings['features'][$class_name]);
		}
		$newval->feature_collection = $this;

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

	/**
	 * Get Feature by Class
	 *
	 * Gets a feature by its class name.
	 *
	 * @param string $class_name The class name of the feature to get.
	 *
	 * @return Feature|bool Either the feature if successful, else false.
	 */
	public function getFeatureByClass($class_name)
	{
		foreach ($this as $feature) {
			if ($feature instanceof $class_name) {
				return $feature;
			}
		}

		return false;
	}

	/**
	 * Get Feature by Type
	 *
	 * Gets a feature by its parent classes.
	 *
	 * @param string $type Any parent class of the feature to get.
	 *
	 * @return Feature|bool Either the feature found, or false.
	 */
	public function getFeatureByType($type)
	{
		$feature_name = $this->satisfyDependency($type);
		if (!$feature_name) {
			return false;
		}
		$feature_factory = FeatureFactory::getInstance();
		$full_feature_name = $feature_factory->getInternalPrefix() .
			$feature_name . $feature_factory->getComponentClassSuffix();
		return $this->getFeatureByClass($full_feature_name);
	}

	protected function resolveDependencies(Feature $feature)
	{
		if ($this->base_command === null || $this->settings === null) {
			throw new FeatureCollectionException('The feature collection has not yet been initialized.');
		}

		foreach ($feature->getDependencies() as $dependency) {
			if (!$this->featureExists($dependency)) {
				// Try to satisfy the dependency.
				$suggested_feature = $this->satisfyDependency($dependency);
				if (!$suggested_feature) {
					throw new \Exception('Feature dependency ' . $dependency . ' could not be satisfied.');
				}
				$this[] = FeatureFactory::getInstance()->factory($suggested_feature, $this->base_command, $this->settings);
			}
		}
	}

	/**
	 * Satisfy Dependency
	 *
	 * Satisfies a dependency by looking at a feature's parent class and the
	 * site settings to get the feature specific to the current site.
	 *
	 * For example, when satisfying the dependency 'Database,' this returns
	 * the MySQLDatabase feature if the current site has a MySQLDatabase
	 * feature section.
	 *
	 * @param string $dependency The name of the dependency to satisfy.
	 *
	 * @return bool|string The found feature name on success, or false on failure.
	 */
	protected function satisfyDependency($dependency)
	{
		$feature_factory = FeatureFactory::getInstance();

		foreach ($this->settings['features'] as $feature_name => $feature) {
			$full_feature_name = $feature_factory->getInternalPrefix() .
				$feature_name . $feature_factory->getComponentClassSuffix();
			$parent_names = Component::getClassParentNames($full_feature_name, $feature_factory);
			foreach ($parent_names as $parent_name) {
				if ($parent_name == $dependency) {
					return $feature_name;
				}
			}
		}

		// If we still haven't found a feature, see if the class is abstract.
		$full_feature_name = $feature_factory->getInternalPrefix() .
			$dependency . $feature_factory->getComponentClassSuffix();
		$reflect = new \ReflectionClass($full_feature_name);
		if ($reflect->isInstantiable()) {
			return $dependency;
		}

		return false;
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
