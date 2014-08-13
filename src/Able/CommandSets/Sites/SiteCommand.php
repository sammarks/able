<?php

namespace Able\CommandSets\Sites;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Logger;
use Docker\AuthConfig;
use Docker\Http\DockerClient;
use Docker\Manager\ImageManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class SiteCommand extends BaseCommand {

	protected $settings = array();
	protected $directory = '';
	protected $last_message = '';
	protected $registry = '';

	protected function configure()
	{
		$this
			->addArgument('directory', InputArgument::OPTIONAL, 'The directory that corresponds to the root of the site repository.', getcwd())
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the image to create or push. Defaults to the name of the repository with the environment appended.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		$this->log('Preparing');

		// Get the directory that houses the repository root.
		$this->directory = $input->getArgument('directory');
		$this->directory = rtrim($this->directory, '/') . '/';
		if (($message = $this->validateRepositoryRoot($this->directory)) !== true) {
			$this->error('The repository root: ' . $this->directory . ' is invalid because: ' . $message, true);
		}

		// Prepare the settings array.
		try {
			$this->settings = $this->getSettings($this->directory);
		} catch (MalformedSettingsException $ex) {
			$this->error('There was an error parsing the settings: ' . $ex->getMessage(), true);
		}

		// Set the registry.
		$this->registry = $this->config->get('docker/registry');
	}

	protected function getSettings($directory)
	{
		$this->log('Parsing ablecore.yaml', 'white', self::DEBUG_VERBOSE);

		$settings_file = $directory . 'config/ablecore.yaml';

		$contents = file_get_contents($settings_file);
		if (!$contents) {
			throw new MalformedSettingsException('The settings file could not be found or could not be loaded (' . $settings_file . ')');
		}

		$settings = Yaml::parse($contents);

		// Add the repository_root key to the settings.
		$settings['repository_root'] = $directory;

		// Merge those settings on top of the defaults.
		$defaults = $this->config->get('site');
		$settings = array_replace_recursive($defaults, $settings);

		// Validate the settings.
		$this->validateSettings($settings);

		return $settings;
	}

	protected function validateSettings($settings)
	{
		$required_keys = array(
			'title',
			'fqdn',
			'webroot',
			'repository_root',
		);
		foreach ($required_keys as $key) {
			if (!array_key_exists($key, $settings)) {
				throw new MalformedSettingsException('The key ' . $key . ' is required, but does not exist in the project settings.');
			}
		}
	}

	protected function validateRepositoryRoot($directory)
	{
		$this->log('Validating the repository root.', 'white', self::DEBUG_VERBOSE);

		// Make sure the repository root is actually a directory.
		if (!is_dir($directory)) return 'Not a directory.';

		// Let's make sure we can find the configuration file.
		$configuration_directory = $directory . 'config/';
		if (!is_dir($configuration_directory)) return 'Configuration directory could not be found (config/).';
		$settings_file = $configuration_directory . 'ablecore.yaml';
		if (!is_file($settings_file)) return 'Settings file could not be found (config/ablecore.yaml).';

		return true;
	}

	protected function getDockerClient()
	{
		// Instantiate the Docker client.
		if (!getenv('DOCKER_HOST')) {

			// Try to get the host from boot2docker.
			if ($this->exec('boot2docker ip 2>/dev/null', false, true)
				&& ($host = $this->exec('boot2docker ip 2>/dev/null', false, false, true)) !== false
				&& is_array($host)
				&& count($host) > 0) {
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

	protected function getDockerAuth()
	{
		$registry_key = ($this->registry) ? $this->registry : 'default';
		$credentials = $this->config->get('docker/auth/' . $registry_key);
		if (!is_array($credentials)) {
			throw new \Exception('The provided Docker credentials for the registry: ' . $registry_key . ' are invalid.');
		}

		foreach (array('username', 'password', 'email') as $check) {
			if (!array_key_exists($check, $credentials)) {
				throw new \Exception('Missing ' . $check . ' for registry ' . $registry_key . '. Please check your config.');
			}
		}

		return new AuthConfig($credentials['username'], $credentials['password'], $credentials['email']);
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
				$this->last_message = '';
			} else {
				if ($output_data->status != $this->last_message) {
					$logger->log($output_data->status);
				}
				$this->last_message = $output_data->status;
			}
		} elseif (!empty($output_data->stream)) {
			$logger->log(trim($output_data->stream, "\n"));
			$this->last_message = '';
		} elseif (!empty($output_data->error)) {
			throw new \Exception('Docker error: ' . $output_data->error);
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

		// Generate an image name from the name of the site.
		$image_name = str_replace(array(' ', '&', '!', '@', '#', '$', '%', '^', '*', '(', ')'), array('-', '-', ''),
			strtolower($this->settings['title']));

		// And then append the environment to it.
		$image_name .= '-' . strtolower($this->settings['environment']);

		return str_replace('-', '_', $image_name);
	}

	protected function getTag()
	{
		$message = $this->input->getOption('message');
		if ($message && preg_match('/[^A-Za-z0-9._]/', $message)) {
			$this->error('Message "' . $message . '" contains invalid characters. Only upper or lowercase alphanumeric characters, underscores and periods are allowed.',
				true);

			return '';
		}

		$date = date('m.d.Y.H.i', time());

		if ($message) {
			return implode('-', array($date, $message));
		} else {
			return $date;
		}
	}

	/**
	 * Find Existing Image
	 *
	 * @param ImageManager $image_manager The Docker image manager.
	 *
	 * @return bool|\Docker\Image Either the existing image if one was found, or false.
	 */
	protected function findExistingImage(ImageManager $image_manager)
	{
		// Get the image name.
		$image_repository = $this->getImageName($this->directory);
		if ($this->registry) {
			$image_repository = $this->registry . '/' . $image_repository;
		}

		// Find the image.
		$images = $image_manager->findAll();
		$current_image = false;
		foreach ($images as $image) {
			if ($image->getRepository() == $image_repository) {
				$current_image = $image;
				break;
			}
		}
		if ($current_image === false) {
			$this->error('An image with the name ' . $image_repository . ' could not be found. This probably means ' .
				'something went wrong.',
				true);

			return false;
		}

		return $current_image;
	}

}
