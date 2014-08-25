<?php

namespace Able\CommandSets\Cluster;

use Able\Helpers\Cluster\Operations\CreateOperation;
use Able\Helpers\Cluster\Operations\OperationFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends ClusterCommand {

	protected function configure()
	{
		$this
			->setName('cluster:create')
			->setDescription('Creates a new cluster of servers on Amazon EC2');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Foreach of the clusters...
		foreach ($this->configuration as $cluster_identifier => $cluster) {
			/** @var OperationFactory $factory */
			$factory = OperationFactory::getInstance();
			/** @var CreateOperation $creator */
			$creator = $factory->operation('Create', $cluster_identifier, $cluster);
			$creator->create($cluster_identifier, $cluster);
		}
	}

} 
