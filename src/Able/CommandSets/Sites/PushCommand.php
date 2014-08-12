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
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Instantiate Docker.
		$docker = new Docker($this->getDockerClient());

		// Get the image name.
		$image_name = $this->getImageName($this->directory) . ':' . $this->getTag();
		if ($this->registry) {
			$image_name = $this->registry . '/' . $image_name;
		}
		$this->log('PUSH ' . $image_name);

		// Get the image.
		$image_manager = $docker->getImageManager();
		$image = $image_manager->find($image_name);
		if (!$image) {
			$this->error('An image with the name ' . $image_name . ' could not be found. This probably means ' .
				'something went wrong.', true);
			return;
		}

		// Push the image.
		$auth = $this->getDockerAuth();
		$image_manager->push($image, $auth, array($this, 'opCallback'));

		$this->log('Success.', 'green');
	}

} 
