<?php

namespace Able\CommandSets\Cluster;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Cluster\Creator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Create extends BaseCommand {

	protected function configure()
	{
		$this
			->setName('cluster:create')
			->setDescription('Creates a new cluster of servers on Amazon EC2')
			->addArgument('config', InputArgument::REQUIRED, 'The location of a YAML configuration file for the cluster.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Make sure the configuration file exists.
		$config_file = $input->getArgument('config');
		if (!file_exists($config_file)) {
			throw new \Exception('The configuration file specified does not exist.');
		}

		// Get the configuration.
		$configuration = Yaml::parse(file_get_contents($config_file));

		// Foreach of the clusters...
		foreach ($configuration as $cluster_identifier => $cluster) {
			$creator = new Creator();
			$creator->create($cluster_identifier, $cluster);
		}
	}

} 
