<?php

namespace Able\CommandSets\Cluster;

use Able\CommandSets\BaseCommand;
use Aws\Ec2\Ec2Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Create extends BaseCommand {

	protected function configure()
	{
		$this
			->setName('cluster:create')
			->setDescription('Creates a new cluster of servers on Amazon EC2')
			->addArgument('name', InputArgument::REQUIRED, 'The base name of the instances. Used for administrative purposes.')
			->addArgument('ami', InputArgument::OPTIONAL, 'The AMI to use for the instances. Defaults to what is in the configuration.')
			->addOption('number', null, InputOption::VALUE_REQUIRED, 'The number of instances to launch.', 1)
			->addOption('key', 'k', InputOption::VALUE_REQUIRED, 'The key to use when connecting to the instances.', '')
			->addOption('type', 't', InputOption::VALUE_REQUIRED, 'The type of instances to create.', 't1.micro')
			->addOption('subnet', 's', InputOption::VALUE_REQUIRED, 'The name of the subnet to add the instances to.')
			->addOption('etcd-token', null, InputOption::VALUE_REQUIRED, 'The token to use for etcd on the servers when they\'re created.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Connect to EC2.
		$this->log('Initializing');
		$ec2 = Ec2Client::factory(array(
			'key' => $this->config->get('aws/access_key'),
			'secret' => $this->config->get('aws/access_secret'),
			'region' => $this->config->get('aws/region'),
		));

		if (!$ec2) {
			$this->error('Connection to EC2 failed. Check your credentials.', true);
			return;
		}

		$this->log('Checking for Security Groups');
		$result = $ec2->describeSecurityGroups(array());
		$groups = $result->getPath('SecurityGroups/*/GroupName');
		if (!in_array('Able-CoreOS', $groups)) {
			$this->log('Creating security group.');
			$result = $ec2->createSecurityGroup(array(
				'GroupName' => 'Able-CoreOS',
				'Description' => 'Servers operating in CoreOS clusters.',
			));
			$id = $result->getPath('GroupId');
			if (!$id) {
				$this->error('There was an error creating the security group.', true);
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
			$ec2->authorizeSecurityGroupEgress(array(
				'GroupId' => $id,
				'IpPermissions' => $ipPermissions,
			));
		}

		// Generate the user data for the instance.
		$user_data = array(
			'coreos' => array(
				'etcd' => array(
					'addr' => '$private_ipv4:4001',
					'peer-addr' => '$private_ipv4:7001',
				),
				'units' => array(
					array(
						'name' => 'etcd.service',
						'command' => 'start',
					),
					array(
						'name' => 'fleet.service',
						'command' => 'start',
					),
				),
			),
		);

		// Generate the etcd token if it wasn't already supplied.
		if ($etcd = $input->getOption('etcd-token')) {
			$user_data['coreos']['etcd']['discovery'] = 'https://discovery.etcd.io/' . $etcd;
		} else {
			$user_data['coreos']['etcd']['discovery'] = $this->generateEtcdToken();
		}

		// Compile the user data.
		$compiled_user_data = Yaml::dump($user_data);

		// Get the AMI.
		$ami = $this->config->get('aws/ami');
		if ($input->getArgument('ami')) {
			$ami = $input->getArgument('ami');
		}

		$instance_configuration = array(
			'ImageId' => $ami,
			'MinCount' => $input->getOption('number'),
			'MaxCount' => $input->getOption('number'),
			'KeyName' => $input->getOption('key'),
			'SecurityGroups' => array('Able-CoreOS'),
			'UserData' => base64_encode($compiled_user_data),
			'InstanceType' => $input->getOption('type'),
		);

		// Add the subnet if it was specified.
		if ($subnet = $input->getOption('subnet')) {
			$instance_configuration['SubnetId'] = $subnet;
		}

		$this->log('Creating ' . $input->getOption('number') . ' instances with the AMI: ' . $ami);
		$response = $ec2->runInstances($instance_configuration);

		if (!is_array($response['Instances'])) {
			$this->log(print_r($response, 1), 'white', self::DEBUG_VERBOSE);
			$this->error('There was an error creating the instances.', true);
		}

		$instance_ids = $response->getPath('Instances/*/InstanceId');
		foreach ($instance_ids as $id) {
			$this->log('CREATE instance ' . $id . ' successful.', 'green');
		}

		$this->log('Waiting for instances to launch.');
		$ec2->waitUntilInstanceRunning(array(
			'InstanceIds' => $instance_ids,
		));

		$this->log('Creating tags for instances.');
		$counter = 0;
		$base = $input->getArgument('name');
		foreach ($instance_ids as $id) {
			$tag = $base . '-' . $counter;
			$counter++;
			$ec2->createTags(array(
				'Resources' => array($id),
				'Tags' => array(
					array(
						'Key' => 'Name',
						'Value' => $tag,
					)
				)
			));
		}

		$result = $ec2->describeInstances(array(
			'InstanceIds' => $instance_ids,
		));

		$instances = $result->getPath('Reservations/*/Instances');
		foreach ($instances as $instance) {
			$this->log('Instance ' . $this->getInstanceName($instance) . ' launched successfully.', 'green');
		}
	}

	protected function getInstanceName($instance)
	{
		foreach ($instance['Tags'] as $tag) {
			if ($tag['Key'] == 'Name') {
				return $tag['Value'];
			}
		}
		return '';
	}

	protected function generateEtcdToken()
	{
		if ($contents = file_get_contents('https://discovery.etcd.io/new')) {
			$this->log('etcd token: ' . $contents, 'white', self::DEBUG_VERBOSE);
			return $contents;
		} else {
			throw new \Exception('There was an error generating an etcd token.');
		}
	}

} 
