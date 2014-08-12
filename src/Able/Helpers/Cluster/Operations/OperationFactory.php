<?php
/**
 * @file OperationFactory.php
 */
namespace Able\Helpers\Cluster\Operations;

use Able\CommandSets\BaseCommand;
use Able\Helpers\ComponentFactory;

class OperationFactory extends ComponentFactory {

	function getComponentClass()
	{
		return 'Able\\Helpers\\Cluster\\Operations\\Operation';
	}

	function getComponentClassSuffix()
	{
		return 'Operation';
	}

	function getInternalPrefix()
	{
		return 'Able\\Helpers\\Cluster\\Operations\\';
	}

	/**
	 * Operation
	 *
	 * This is the factory method. Gets the operation class instance.
	 *
	 * @param string      $type          The type of operation to get.
	 * @param BaseCommand $command       The command the operation will use to execute commands.
	 * @param string      $name          The name of the cluster.
	 * @param array       $configuration The configuration for the cluster (cluster-specific, not root).
	 *
	 * @return Operation The operation.
	 * @throws \Exception
	 */
	public function operation($type, BaseCommand $command, $name, array $configuration = array())
	{
		$component = $this->factory($type, $command, $configuration);
		if (!($component instanceof Operation)) {
			throw new \Exception('The returned item is not an instance of the Operation class.');
		}

		// Setup the configuration for the component.
		$component->setupConfiguration($name);

		return $component;
	}

}
