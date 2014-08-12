<?php

namespace Able\CommandSets\Sites;

use Able\Helpers\Cluster\Operations\DeployOperation;
use Able\Helpers\Cluster\Operations\OperationFactory;
use Docker\Docker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends SiteCommand {

	protected function configure()
	{
		$this
			->setName('site:deploy')
			->setDescription('Deploys the specified site to the specified cluster.')
			->addArgument('cluster', InputArgument::REQUIRED, 'The cluster to submit the site to.');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Initialize docker.
		$docker = new Docker($this->getDockerClient());
		$image_manager = $docker->getImageManager();

		$image = $this->findExistingImage($image_manager);
		$cluster = $input->getArgument('cluster');
		$this->log('DEPLOY ' . $image->getName() . ' to ' . $cluster);

		/** @var OperationFactory $factory */
		$factory = OperationFactory::getInstance();
		/** @var DeployOperation $deployer */
		$deployer = $factory->operation('Deploy', $this, $cluster);
		$deployer->deploy($this->settings, $image->getName());

		$this->log('Success.', 'green');
	}

} 
