<?php

namespace Able\CommandSets\Cluster;

use Able\CommandSets\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class ClusterCommand extends BaseCommand {

	/**
	 * The configuration for the current cluster.
	 * @var array
	 */
	protected $configuration = array();

	protected function configure()
	{
		$this
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
		$this->configuration = Yaml::parse(file_get_contents($config_file));
	}

} 
