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
			->addArgument('ami', InputArgument::OPTIONAL, 'The AMI to use for the instances. Defaults to what is in the configuration.')
			->addOption('number', null, InputOption::VALUE_REQUIRED, 'The number of instances to launch.', 1)
			->addOption('security-groups', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'The security groups to add the instances to.', array('default'))
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
			'SecurityGroups' => $input->getOption('security-groups'),
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
		$ec2->waitUntilInstanceRunning($instance_ids);
		$result = $ec2->describeInstances(array(
			'InstanceIds' => $instance_ids,
		));

		$dns_names = $result->getPath('Reservations/*/Instances/*/PublicDnsName');
		foreach ($dns_names as $name) {
			$this->log('Instance ' . $name . ' launched successfully.', 'green');
		}
	}

	protected function generateEtcdToken()
	{
		if ($contents = file_get_contents('https://discovery.etcd.io/new')) {
			return $contents;
		} else {
			throw new \Exception('There was an error generating an etcd token.');
		}
	}

} 
