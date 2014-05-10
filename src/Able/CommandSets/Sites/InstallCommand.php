<?php

namespace Able\CommandSets\Sites;

use Able\Helpers\Install\SiteInstallerFactory;
use Able\Helpers\Install\VHostConfigManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;

class InstallCommand extends BaseCommand {

	protected function configure()
	{
		$this
			->setName('site:install')
			->setDescription('Install a website into a Docker instance.')
			->addArgument('directory',
				InputArgument::REQUIRED, 'The repository root directory.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		$this->log('Preparing');

		// Get the directory that houses the repository root.
		$directory = $input->getArgument('directory');
		$directory = trim($directory, '/') . '/';
		if (($message = $this->validateRepositoryRoot($directory)) !== true) {
			$this->error('The repository root: ' . $directory . ' is invalid because: ' . $message, true);
		}

		// Prepare the settings array.
		$settings = array();
		try {
			$settings = $this->getSettings($directory);
		} catch (MalformedSettingsException $ex) {
			$this->error('There was an error parsing the settings: ' . $ex->getMessage(), true);
		}

		$this->log('Installing');

		// Call the SiteInstaller factory to get the appropriate site installer for the current type.
		$type = $settings['type'];
		$installer = SiteInstallerFactory::installer($type, $this, $settings);
		$installer->install();

	}

	protected function validateRepositoryRoot($directory)
	{
		$this->log('Validating the repository root.', 'white', self::DEBUG_VERBOSE);

		// Make sure the repository root is actually a directory.
		if (!is_dir($directory)) return 'Not a directory.';

		// Let's make sure we can find the configuration file.
		$configuration_directory = $directory . 'config/';
		if (!is_dir($configuration_directory)) return 'Configuration directory could not be found (config/).';
		$settings_file = $configuration_directory . 'ablecore.json';
		if (!is_file($settings_file)) return 'Settings file could not be found (config/ablecore.json).';

		return true;
	}

	protected function getSettings($directory)
	{
		$this->log('Parsing ablecore.json', 'white', self::DEBUG_VERBOSE);

		$settings_file = $directory . 'config/ablecore.json';

		$contents = file_get_contents($settings_file);
		if (!$contents) {
			throw new MalformedSettingsException('The settings file could not be found or could not be loaded (' . $settings_file . ')');
		}

		$settings = json_decode($contents);
		if ($settings === null) {
			throw new MalformedSettingsException('The settings contains invalid JSON or could not be decoded.');
		}

		// Add the repository-root key to the settings.
		$settings['repository-root'] = $directory;

		// Validate the settings.
		$this->validateSettings($settings);
		$this->fillDefaults($settings);

		return $settings;
	}

	protected function validateSettings($settings)
	{
		$required_keys = array(
			'title',
			'fqdn',
			'webroot',
			'repository-root',
		);
		foreach ($required_keys as $key) {
			if (!array_key_exists($key, $settings)) {
				throw new MalformedSettingsException('The key ' . $key . ' is required, but does not exist in the project settings.');
			}
		}
	}

	protected function fillDefaults(&$settings)
	{
		$defaults = array(
			'environment' => 'development',
			'type' => 'Basic',
		);
		$settings = array_replace_recursive($settings, $defaults);
	}

} 

class MalformedSettingsException extends \Exception {}
