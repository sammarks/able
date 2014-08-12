<?php

namespace Able\Helpers\Cluster\Providers;

use Able\CommandSets\BaseCommand;
use Able\Helpers\ConfigurationManager;
use Able\Helpers\Logger;
use Aws\Ec2\Ec2Client;
use Symfony\Component\Yaml\Yaml;

class EC2Provider extends Provider {

	public function createNode()
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		/** @var ConfigurationManager $config */
		$config = ConfigurationManager::getInstance();

		$logger->log('Connecting to Amazon', 'white', BaseCommand::DEBUG_VERBOSE);
		$ec2 = Ec2Client::factory(array(
			'key' => $config->get('aws/access_key'),
			'secret' => $config->get('aws/access_secret'),
			'region' => $this->settings['region'],
		));

		if (!$ec2) {
			$logger->error('Connection to EC2 failed. Check your credentials.', true);
			return;
		}

		// Create the security group if we need to.
		$this->verifySecurityGroups($ec2);
		$security_group = $this->getSecurityGroupID($ec2);
		if (!$security_group) {
			$logger->error('The Able-CoreOS security group could not be created.', true);
			return;
		}

		// Generate the compiled user data.
		$compiled_user_data = "#cloud-config\n\n" . Yaml::dump($this->node_settings['cloud-config']);

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
			$instance_configuration['SubnetId'] = $this->settings['subnet'];
		}

		// Actually create the instance.
		$logger->log('CREATE ' . $this->identifier);
		$response = $ec2->runInstances($instance_configuration);

		if (!is_array($response['Instances'])) {
			$logger->log(print_r($response, 1), 'white', BaseCommand::DEBUG_VERBOSE);
			$logger->error('There was an error creating the node ' . $this->identifier . '.', true);
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
						'Value' => $this->node_settings['full-identifier'],
					)
				)
			));
		}

		$logger->log('Successful.', 'green');
	}

	public function getMetadata()
	{
		return array(
			'provider' => 'EC2',
			'region' => $this->settings['region'],
			'type' => $this->settings['type'],
			'identifier' => $this->identifier,
		);
	}

	protected function getSecurityGroupID(Ec2Client $ec2)
	{
		$result = $ec2->describeSecurityGroups(array());
		$groups = $result->getPath('SecurityGroups/*');
		foreach ($groups as $group) {
			if ($group['groupName'] == 'Able-CoreOS') {
				return $group['groupId'];
			}
		}

		return false;
	}

	protected function verifySecurityGroups(Ec2Client $ec2)
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		$logger->log('Checking for Security Groups', 'white', BaseCommand::DEBUG_VERBOSE);
		$result = $ec2->describeSecurityGroups(array());
		$groups = $result->getPath('SecurityGroups/*/GroupName');
		if (!in_array('Able-CoreOS', $groups)) {
			$logger->log('Creating security group.', 'white', BaseCommand::DEBUG_VERBOSE);
			$result = $ec2->createSecurityGroup(array(
				'GroupName' => 'Able-CoreOS',
				'Description' => 'Servers operating in CoreOS clusters.',
			));
			$id = $result->getPath('GroupId');
			if (!$id) {
				$logger->error('There was an error creating the security group.', true);
				return;
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
		}
	}

}
