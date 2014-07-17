<?php

namespace Able\CommandSets\Sites;

use Able\Helpers\Logger;
use Docker\Context\Context;
use Docker\Docker;
use Docker\Http\DockerClient;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;

class DeployCommand extends BaseCommand
{

	protected function configure()
	{
		$this
			->setName('site:deploy')
			->setDescription('Deploy a Website')
			->addArgument('directory', InputArgument::OPTIONAL, 'The directory that corresponds to the root of the site repository.', getcwd())
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the image to create. Defaults to the name of the repository with the environment appended.')
			->addOption('no-cache', null, InputOption::VALUE_NONE, 'If this is set, the Docker cache will not be used.')
			->addOption('no-rm', null, InputOption::VALUE_NONE, 'If this is set, the intermediate container will not be removed after the image is created.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Make sure the directory exists.
		$directory = $input->getArgument('directory');
		if (!is_dir($directory)) {
			$this->error('The directory ' . $directory . ' does not exist.', true);
			return;
		}

		// Make sure the directory contains a dockerfile.
		if (!file_exists($directory . DIRECTORY_SEPARATOR . 'Dockerfile')) {
			$this->error('The directory ' . $directory . ' does not contain a Dockerfile.', true);
			return;
		}

		// Instantiate docker.
		$docker = new Docker($this->getDockerClient());

		$this->log('BUILD ' . $directory . DIRECTORY_SEPARATOR . 'Dockerfile');

		// Get the context.
		$context = new Context($directory);
		$docker->build($context, $this->getImageName($directory), array(get_class(), 'buildCallback'), false,
			!$input->getOption('no-cache'), !$input->getOption('no-rm'));

		$this->log('Successful.', 'green');
	}

	protected function getDockerClient()
	{
		// Instantiate the Docker client.
		if (!getenv('DOCKER_HOST')) {

			// Try to get the host from boot2docker.
			if (($host = $this->exec('boot2docker ip 2>/dev/null', false)) !== false) {
				return new DockerClient(array(), 'tcp://' . $host . ':2375');
			} else {
				return new DockerClient(array(), $this->config->get('docker.connection'));
			}

		} else {
			return new DockerClient();
		}
	}

	protected function getImageName($directory)
	{
		$supplied_name = $this->input->getOption('name');
		if ($supplied_name) {
			if (strpos($supplied_name, '/') !== false) {
				return $supplied_name;
			} else {
				return $this->config->get('docker.registry') . '/' . $supplied_name;
			}
		}

		$directory = realpath($directory);
		$directory_segments = explode('/', $directory);
		$image_name = $directory_segments[count($directory_segments) - 1];
		return $this->config->get('docker.registry') . '/' . $image_name;
	}

	public static function buildCallback($output, $type)
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		if ($type === 1) {
			$logger->log($output, 'white', BaseCommand::DEBUG_VERBOSE);
		} else {
			$logger->error($output);
		}
	}

}
