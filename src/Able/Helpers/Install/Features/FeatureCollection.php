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

}

class FeatureCollectionException extends \Exception {}
