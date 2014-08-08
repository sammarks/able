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

	/**
	 * The last image ID of the transaction.
	 * @var string
	 */
	protected $image_id = null;

	/**
	 * The registry to push the image to.
	 * @var string
	 */
	protected $registry = '';

	protected function configure()
	{
		$this
			->setName('site:deploy')
			->setDescription('Deploy a Website')
			->addArgument('directory', InputArgument::OPTIONAL, 'The directory that corresponds to the root of the site repository.', getcwd())
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the image to create. Defaults to the name of the repository with the environment appended.')
			->addOption('no-cache', null, InputOption::VALUE_NONE, 'If this is set, the Docker cache will not be used.')
			->addOption('no-rm', null, InputOption::VALUE_NONE, 'If this is set, the intermediate container will not be removed after the image is created.')
			->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'The message to append to the tag of the image when deploying.');
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
		$this->registry = $this->config->get('docker/registry');

		$this->log('BUILD ' . $directory . DIRECTORY_SEPARATOR . 'Dockerfile');

		// Get the context.
		$context = new Context($directory);
		$no_cache = ($input->getOption('no-cache') != null);
		$no_rm = ($input->getOption('no-rm') != null);

		// Build the image.
		$image_name = $this->getImageName($directory) . ':' . $this->getTag();
		$docker->build($context, $image_name, array($this, 'opCallback'),
			$output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE, !$no_cache, !$no_rm);

		// Tag the image with the current date (and an optional description).
		$image_manager = $docker->getImageManager();

		// Get the image.
		$image = $image_manager->find($image_name);
		if (!$image) {
			$this->error('An image with the ID ' . $image_name . ' could not be found. This probably means ' .
				'something went wrong.', true);
			return;
		}

		// Push the image.
		$this->log('PUSH ' . $image_name);
		$image_manager->push($image, array($this, 'opCallback'), $this->registry);

		$this->log('Successful.', 'green');
	}

	protected function getDockerClient()
	{
		// Instantiate the Docker client.
		if (!getenv('DOCKER_HOST')) {

			// Try to get the host from boot2docker.
			if (($host = $this->exec('boot2docker ip 2>/dev/null', false, false, true)) !== false && is_array($host) && count($host) > 0) {
				$host_ip = null;
				foreach ($host as $potential) {
					if (!$potential) continue;
					if (strpos($potential, ':') !== false) {
						$host_segments = explode(':', $potential);
						$host_ip = trim($host_segments[count($host_segments) - 1]);
					}
				}
				if (!$host_ip) {
					$this->error('A host IP could not be found for Docker.', true);
					return null;
				}
				return new DockerClient(array(), 'tcp://' . $host_ip . ':2375');
			} else {
				return new DockerClient(array(), $this->config->get('docker/connection'));
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
				$segments = explode('/', $supplied_name);
				if (count($segments) !== 2) {
					$this->error($supplied_name . ' is an invalid image name.', true);
					return '';
				}
				$this->registry = $segments[0];
				return $segments[1];
			} else {
				return $supplied_name;
			}
		}

		$directory = realpath($directory);
		$directory_segments = explode('/', $directory);
		$image_name = $directory_segments[count($directory_segments) - 1];
		return $image_name;
	}

	protected function getTag()
	{
		$message = $this->input->getOption('message');
		if ($message && preg_match('/[^A-Za-z0-9.-]/', $message)) {
			$this->error('Message "' . $message . '" contains invalid characters. Only upper or lowercase alphanumeric characters, hyphens and periods are allowed.', true);
			return '';
		}

		$date = date('m.d.Y.H.i', time());

		if ($message) {
			return implode('-', array($date, $message));
		} else {
			return $date;
		}
	}

	public function opCallback($output)
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();

		$output_data = json_decode($output);
		if (!empty($output_data->status)) {
			if (!empty($output_data->progressDetail->current) && !empty($output_data->progressDetail->total)) {
				$percentage = floor(($output_data->progressDetail->current / $output_data->progressDetail->total) * 100);
				$human_start = self::formatSizeUnits($output_data->progressDetail->current);
				$human_end = self::formatSizeUnits($output_data->progressDetail->total);
				$logger->overwrite('Complete: ' . $percentage . '% (' . $human_start . '/' . $human_end . ')');
			} else {
				$logger->log($output_data->status);
			}
		} elseif (!empty($output_data->stream)) {
			$logger->log(trim($output_data->stream, "\n"));
		}
	}

	// Snippet from PHP Share: http://www.phpshare.org
	protected static function formatSizeUnits($bytes)
    {
	    if ($bytes >= 1073741824) {
		    $bytes = number_format($bytes / 1073741824, 2) . ' GB';
	    } elseif ($bytes >= 1048576) {
		    $bytes = number_format($bytes / 1048576, 2) . ' MB';
	    } elseif ($bytes >= 1024) {
		    $bytes = number_format($bytes / 1024, 2) . ' KB';
	    } elseif ($bytes > 1) {
		    $bytes = $bytes . ' bytes';
	    } elseif ($bytes == 1) {
		    $bytes = $bytes . ' byte';
	    } else {
		    $bytes = '0 bytes';
	    }

	    return $bytes;
    }

}
