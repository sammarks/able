<?php

namespace Able\CommandSets\Sites;

use Able\CommandSets\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class SiteCommand extends BaseCommand {

	protected $settings = array();
	protected $directory = '';

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

}
