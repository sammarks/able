<?php

namespace Able\Helpers\Cluster\Providers;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Cluster\Cluster;
use Able\Helpers\Cluster\Node;
use Able\Helpers\ConfigurationManager;
use Able\Helpers\Logger;
use Aws\Ec2\Ec2Client;
use Symfony\Component\Yaml\Yaml;

class EC2Provider extends Provider {

	/**
	 * {@inheritDoc}
	 */
	public function createNode($identifier, array $node_settings)
	{
		parent::createNode($identifier, $node_settings);

		/** @var Logger $logger */
		$logger = Logger::getInstance();

		// Initialize EC2.
		$ec2 = $this->getEC2();

		// Get information about the subnet.
		if ($this->settings['subnet']) {
			$subnet = $this->getSubnet($ec2, $this->settings['subnet']);
			if (!$subnet) {
				$logger->error('A subnet was specified, but could not be found.', true);
				return;
			}
		} else {
			$subnet = array();
		}

		// Create the security group if we need to.
		if (array_key_exists('VpcId', $subnet)) {
			$security_group = $this->verifySecurityGroups($ec2, $subnet['VpcId']);
		} else {
			$security_group = $this->verifySecurityGroups($ec2);
		}
		if (!$security_group) {
			$logger->error('The Able-CoreOS security group could not be created.', true);
			return;
		}

		// Generate the compiled user data.
		$compiled_user_data = "#cloud-config\n\n" . Yaml::dump($node_settings['cloud-config']);

		// Get the AMI.
		$ami = $this->settings['ami'];

		// Prepare the instance configuration.
		$instance_configuration = array(
			'ImageId' => $ami,
			'MinCount' => 1,
			'MaxCount' => 1,
			'KeyName' => $this->settings['key'],
			'SecurityGroupIds' => array($security_group),
			'UserData' => base64_encode($compiled_user_data),
			'InstanceType' => $this->settings['type'],
		);

		if (!empty($this->settings['subnet'])) {
			unset($instance_configuration['SecurityGroupIds']);
			$instance_configuration['NetworkInterfaces'] = array(array(
				'DeviceIndex' => 0,
				'SubnetId' => $this->settings['subnet'],
				'Description' => 'primary',
				'Groups' => array($security_group),
				'DeleteOnTermination' => true,
				'AssociatePublicIpAddress' => true,
			));
		}

		// Actually create the instance.
		$logger->log('CREATE ' . $identifier);
		$response = $ec2->runInstances($instance_configuration);

		if (!is_array($response['Instances'])) {
			$logger->log(print_r($response, 1), 'white', BaseCommand::DEBUG_VERBOSE);
			$logger->error('There was an error creating the node ' . $identifier . '.', true);
			return;
		}

		// Tag the instances.
		$instance_ids = $response->getPath('Instances/*/InstanceId');

		$logger->log('Waiting for instance to launch.', 'white', BaseCommand::DEBUG_VERBOSE);
		$error = true;
		$tries = 0;
		while ($error === false && $tries < 3) {
			try {
				$tries++;
				$ec2->waitUntilInstanceRunning(array(
					'InstanceIds' => $instance_ids,
				));
			} catch (\Exception $ex) {
				$logger->log('Failed. Trying again.', 'red', BaseCommand::DEBUG_VERBOSE);
			}
		}

		$logger->log('Creating tags for instance.', 'white', BaseCommand::DEBUG_VERBOSE);
		foreach ($instance_ids as $id) {
			$ec2->createTags(array(
				'Resources' => array($id),
				'Tags' => array(
					array(
						'Key' => 'Name',
						'Value' => $node_settings['full-identifier'],
					),
					array(
						'Key' => 'Cluster',
						'Value' => $node_settings['cluster'],
					)
				)
			));
		}

		$logger->log('Successful.', 'green');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMetadata($identifier)
	{
		return array(
			'provider' => 'EC2',
			'region' => $this->settings['region'],
			'type' => $this->settings['type'],
			'identifier' => $identifier,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getNodes()
	{
		$ec2 = $this->getEC2();
		$result = $ec2->describeInstances(array(
			'Filters' => array(
				array(
					'Name' => 'tag:Cluster',
					'Values' => array($this->cluster->getName()),
				)
			)
		));
		$instances = $result->getPath('Reservations/*/ReservationId');

		if (count($instances) > 0) {

			$nodes = array();
			foreach ($instances as $instance) {
				$nodes[] = $this->inspectNode($instance);
			}
			return $nodes;

		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function inspectNode($node_identifier)
	{
		$ec2 = $this->getEC2();
		$result = $ec2->describeInstances(array(
			array(
				'InstanceIds' => array($node_identifier),
			),
		));
		$reservation = $result->getPath('Reservations/0/Instances/0');
		if (!$reservation) {
			throw new \Exception('The node ' . $node_identifier . ' could not be found.');
		}

		// Get the name of the node.
		$name = false;
		foreach ($reservation['Tags'] as $tag) {
			if ($tag['Key'] == 'Name') {
				$name = $tag['Value'];
				break;
			}
		}
		if (!$name) {
			throw new \Exception('The name for the node ' . $node_identifier . ' could not be found.');
		}

		return new Node($name, $this->cluster, 'EC2', $reservation['InstanceId'], $reservation);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getNodePrivateIp(Node $node)
	{
		return $node->getAttributes()['PrivateIpAddress'];
	}

	protected function getSubnet(Ec2Client $ec2, $subnet_id = '')
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		$logger->log('Fetching additional information about subnets.', 'white', BaseCommand::DEBUG_VERBOSE);
		$result = $ec2->describeSubnets(array(
			'SubnetIds' => array($subnet_id),
		));
		$subnet = $result->getPath('Subnets/0');
		if ($subnet && is_array($subnet)) {
			return $subnet;
		}

		return false;
	}

	protected function verifySecurityGroups(Ec2Client $ec2, $vpc_id = false)
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		$logger->log('Checking for Security Groups', 'white', BaseCommand::DEBUG_VERBOSE);

		// Prepare the arguments.
		$arguments = array();
		if ($vpc_id !== false) {
			$arguments = array(
				'Filters' => array(
					array(
						'Name' => 'vpc-id',
						'Values' => array($vpc_id),
					)
				)
			);
		}

		$result = $ec2->describeSecurityGroups($arguments);
		$groups = $result->getPath('SecurityGroups/*/GroupName');
		$group_ids = $result->getPath('SecurityGroups/*/GroupId');
		if (!in_array('Able-CoreOS', $groups)) {
			$logger->log('Creating security group.', 'white', BaseCommand::DEBUG_VERBOSE);

			// Generate the creation arguments.
			$create_args = array(
				'GroupName' => 'Able-CoreOS',
				'Description' => 'Servers operating in CoreOS clusters.',
			);
			if ($vpc_id !== false) {
				$create_args['VpcId'] = $vpc_id;
			}

			$result = $ec2->createSecurityGroup($create_args);
			$id = $result->getPath('GroupId');
			if (!$id) {
				$logger->error('There was an error creating the security group.', true);
				return false;
			}
			$ipPermissions = array(
				array(
					'IpProtocol' => 'tcp',
					'FromPort' => 22,
					'ToPort' => 22,
					'IpRanges' => array(array('CidrIp' => '0.0.0.0/0'))
				),
				array(
					'IpProtocol' => 'tcp',
					'FromPort' => 80,
					'ToPort' => 80,
					'IpRanges' => array(array('CidrIp' => '0.0.0.0/0'))
				),
				array(
					'IpProtocol' => 'tcp',
					'FromPort' => 4001,
					'ToPort' => 4001,
					'IpRanges' => array(array('CidrIp' => '0.0.0.0/0')),
				),
				array(
					'IpProtocol' => 'tcp',
					'FromPort' => 7001,
					'ToPort' => 7001,
					'IpRanges' => array(array('CidrIp' => '0.0.0.0/0')),
				),
			);
			$ec2->authorizeSecurityGroupIngress(array(
				'GroupId' => $id,
				'IpPermissions' => $ipPermissions,
			));
			return $id;
		} else {
			$index = array_search('Able-CoreOS', $groups);
			return $group_ids[$index];
		}
	}

	/**
	 * Get EC2
	 *
	 * @return Ec2Client|bool Either the EC2 client on success, or false on failure.
	 */
	protected function getEC2()
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		/** @var ConfigurationManager $config */
		$config = ConfigurationManager::getInstance();

		$logger->log('Connecting to Amazon', 'white', BaseCommand::DEBUG_VERBOSE);
		$ec2 = Ec2Client::factory(array(
			'key' => $config->get('aws/access_key'),
			'secret' => $config->get('aws/access_secret'),
			'region' => $this->region,
		));

		if (!$ec2) {
			$logger->error('Connection to EC2 failed. Check your credentials.', true);

			return false;
		}

		return $ec2;
	}

}
