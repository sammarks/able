<?php

namespace Able\CommandSets\Sites;

use Able\Helpers\Install\ConfigurationManagers\ConfigurationManager;
use Able\Helpers\Install\Features\FeatureCollection;
use Able\Helpers\Install\Features\FeatureFactory;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManagerFactory;
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
		$directory = rtrim($directory, '/') . '/';
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

		// Install the site.
		$this->install($settings);

		$this->log('Complete!', 'green');

	}

	protected function install(array $settings)
	{
		// Prepare the features for the site.
		$features = new FeatureCollection();

		// Initialize the feature collection.
		$features->initialize($this, $settings);

		// Add the site feature.
		$features->append(FeatureFactory::getInstance()->factory('Site', $this, $settings));

		// Add the environment feature.
		$features->append(FeatureFactory::getInstance()->factory($settings['environment'], $this, $settings));

		// Add other features from the settings.
		$this->getFeatures($features, $settings);

		// Handle the configuration for the site.
		$this->log('Preparing Server Configuration', 'white', self::DEBUG_VERBOSE);
		$this->handleConfigurations($features, $settings);

		// Get the copy destination for the files from the feautres.
		// If no feature specifies, we default to the webroot.
		$directory = $settings['webroot_folder'] . '/' . $settings['webroot'];
		$directory = $features->alterHook('alterWebroot', $directory);

		// Call the pre-copy hook.
		$features->callHook('preCopy', $directory);

		// Copy the files from the repository docroot to the destination.
		$this->log('Copying docroot', 'white', self::DEBUG_VERBOSE);
		$this->copyToWebroot($settings, $directory);

		// Call the post-copy hook.
		$features->callHook('postCopy', $directory);

		// Restart nginx and php5-fpm.
		$this->log('Restarting Services', 'white', self::DEBUG_VERBOSE);
		$this->restartServices();

		// Call the post-restart-services hook.
		$features->callHook('postRestartServices');
	}

	protected function restartServices()
	{
		$this->exec('service nginx restart');
		$this->exec('service php5-fpm restart');
	}

	protected function copyToWebroot(array $settings, $destination)
	{
		$docroot = $settings['repository_root'] . 'docroot';
		if (!is_dir($docroot)) {
			throw new SiteInstallException('The repository docroot folder ' . $docroot . ' does not exist.');
		}

		if (!is_dir($destination)) {
			mkdir($destination, 0777, true);
		}

		if (!is_dir($destination)) {
			throw new SiteInstallException('The destination directory ' . $docroot . ' does not exist, and an attempt to create the directory failed.');
		}

		$this->exec("cp -r '$docroot' '$destination'");
	}

	protected function handleConfigurations(FeatureCollection $features, array $settings)
	{
		foreach ($settings['configuration'] as $key => $config) {
			$configuration = ConfigurationManagerFactory::getInstance()->factory($key, $this, $settings);
			if (!($configuration instanceof ConfigurationManager)) continue;
			$configuration->setFeatureCollection($features);
			$configuration->save();
		}
	}

	protected function getFeatures(FeatureCollection &$features, array $settings)
	{
		foreach ($settings['features'] as $feature => $configuration) {
			if (!is_array($configuration)) { // Here we're able to accept either an array or a string without a value.
				$feature = $configuration;
			}
			$features->append(FeatureFactory::getInstance()->factory($feature, $this, $settings));
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

		$settings = (array)json_decode($contents);
		if ($settings === null) {
			throw new MalformedSettingsException('The settings contains invalid JSON or could not be decoded.');
		}

		// Add the repository_root key to the settings.
		$settings['repository_root'] = $directory;

		// Merge those settings on top of the defaults.
		$defaults = $this->config['site'];
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

} 

class MalformedSettingsException extends \Exception {}
class SiteInstallException extends \Exception {}
