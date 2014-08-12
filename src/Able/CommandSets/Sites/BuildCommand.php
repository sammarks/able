<?php

namespace Able\CommandSets\Sites;

use Docker\Context\Context;
use Docker\Docker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends SiteCommand {

	protected function configure()
	{
		$this
			->setName('site:deploy')
			->setDescription('Deploy a Website')
			->addOption('no-cache', null, InputOption::VALUE_NONE, 'If this is set, the Docker cache will not be used.')
			->addOption('no-rm', null, InputOption::VALUE_NONE, 'If this is set, the intermediate container will not be removed after the image is created.')
			->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'The message to append to the tag of the image when deploying.');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Make sure the directory contains a dockerfile.
		if (!file_exists($this->directory . DIRECTORY_SEPARATOR . 'Dockerfile')) {
			$this->error('The directory ' . $this->directory . ' does not contain a Dockerfile.', true);
			return;
		}

		// Instantiate docker.
		$docker = new Docker($this->getDockerClient());
		$this->log('BUILD ' . $this->directory . DIRECTORY_SEPARATOR . 'Dockerfile');

		// Get the context.
		$context = new Context($this->directory);
		$no_cache = ($input->getOption('no-cache') != null);
		$no_rm = ($input->getOption('no-rm') != null);

		// Build the image.
		$image_name = $this->getImageName($this->directory) . ':' . $this->getTag();
		if ($this->registry) {
			$image_name = $this->registry . '/' . $image_name;
		}

		$docker->build($context, $image_name, array($this, 'opCallback'),
			$output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE, !$no_cache, !$no_rm);

		$this->log('Success.', 'green');
	}

}
