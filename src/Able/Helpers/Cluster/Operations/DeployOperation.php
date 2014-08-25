<?php

namespace Able\Helpers\Cluster\Operations;

use Able\Helpers\Cluster\Cluster;
use Able\Helpers\Cluster\Node;
use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\Fleet;
use Able\Helpers\FleetException;
use Able\Helpers\ConfigurationManager;

class DeployOperation extends Operation {

	public function deploy(array $settings, $image_name)
	{
		// Select the node to deploy to from the cluster.
		$this->selectNode($this->cluster);

		// For each node in the cluster...
		foreach ($this->cluster->getNodes() as $index => $node) {

			// Generate the unit file.
			$path = $this->generateUnit($index, $settings, $image_name);

			// Submit the unit file.
			$this->submitUnit($path, $node);

		}
	}

	protected function postInitialize()
	{
		// Make sure fleetctl is installed.
		$this->verifyFleet();
	}

	/**
	 * Verify Fleet
	 *
	 * Instantiates the Fleet wrapper class for the first time to ensure that Fleet
	 * is actually installed.
	 */
	protected function verifyFleet()
	{
		try {
			Fleet::getInstance();
		} catch (FleetException $ex) {
			Logger::getInstance()->error('There was an error initializing fleet: ' . $ex->getMessage(), true);
		}
	}

	/**
	 * Select Node
	 *
	 * @param Cluster $cluster The name of the cluster.
	 *
	 * @return Node A randomly-selected node.
	 * @throws \Exception
	 */
	protected function selectNode(Cluster $cluster)
	{
		$nodes = $cluster->getNodes();

		// Select one of the nodes at random.
		$index = -1;
		$counter = 0;
		while (!array_key_exists($index, $nodes) && $counter < 20) {
			$index = rand(0, count($nodes) - 1);
			$counter++;
		}

		if (!array_key_exists($index, $nodes)) {
			throw new \Exception('There are no nodes to be selected in the ' . $cluster->getName() . ' cluster.');
		}

		return $nodes[$index];
	}

	/**
	 * Generate Unit
	 *
	 * @param int    $index      The index of the unit file.
	 * @param array  $settings   The settings for the site.
	 * @param string $image_name The image for the site.
	 *
	 * @return string The path to the generated unit file.
	 * @throws \Exception
	 */
	protected function generateUnit($index, array $settings, $image_name)
	{
		$unit = array(
			'[Unit]',
			'Description=Web dyno for ' . $settings['title'] . ', image ' . $image_name,
			'After=docker.service',
			'Requires=docker.service',
			'',
			'[Service]',
			'TimeoutstartSec=0',
		);

		$docker_name = $settings['fqdn'] . '-' . strtolower($settings['environment']) . '-' . $index;
		$unit[] = "ExecStartPre=-/usr/bin/docker kill '{$docker_name}'";
		$unit[] = "ExecStartPre=-/usr/bin/docker rm '{$docker_name}'";
		$unit[] = "ExecStartPre=/usr/bin/docker pull '{$image_name}'";
		$unit[] = "ExecStart=/usr/bin/docker run '{$image_name}' --name '{$docker_name}' -p 80:80";

		$unit_filename = 'dyno.' . $docker_name . '.' . $index . '.service';
		$unit_wildcard_name = 'dyno.' . $docker_name . '.*.service';

		$unit[] = '';
		$unit[] = '[X-Fleet]';
		$unit[] = "X-Conflicts={$unit_wildcard_name}";

		$config = ConfigurationManager::getInstance();
		$unit_contents = implode("\n", $unit);
		$unit_path = $config->get('fleet/unit_storage') . DIRECTORY_SEPARATOR . $unit_filename;

		if (!is_writable($unit_path)) {
			throw new \Exception('The path ' . $unit_path . ' is not writable.');
		}

		if (!file_put_contents($unit_path, $unit_contents)) {
			throw new \Exception('There was an error writing the service to ' . $unit_path . '.');
		}

		return $unit_path;
	}

	/**
	 * Submit Unit
	 *
	 * Submits a unit to the specified node.
	 *
	 * @param string $path The path to the generated unit file.
	 * @param Node   $node The node to submit the unit to.
	 */
	protected function submitUnit($path, Node $node)
	{
		$fleet = new Fleet();
		$fleet->setTunnel($node->getInternalIpAddress());
		$fleet->submitUnit($path);
	}

}
