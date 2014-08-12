<?php

namespace Able\CommandSets\Sites;

use Docker\Docker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PushCommand extends SiteCommand {

	protected function configure()
	{
		$this
			->setName('site:push')
			->setDescription('Pushes the current site\'s Docker container to the specified cluster.')
			->addArgument('cluster', InputArgument::REQUIRED, 'The name of the cluster to push to.');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Instantiate Docker.
		$docker = new Docker($this->getDockerClient());
		$image_manager = $docker->getImageManager();



		$this->log('PUSH ' . $current_image->getName());

		// Push the image.
		$auth = $this->getDockerAuth();
		$image_manager->push($current_image, $auth, array($this, 'opCallback'));

		$this->log('Success.', 'green');
	}

} 
