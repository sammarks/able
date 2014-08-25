<?php

namespace Able\CommandSets\Sites;

use Able\Helpers\CommandHelpers\Executer;
use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManager;
use Able\Helpers\Install\Features\FeatureCollection;
use Able\Helpers\Install\Features\FeatureFactory;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManagerFactory;
use Able\Helpers\ScopeManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends SiteCommand {

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

		Logger::getInstance()->log('Installing');

		// Install the site.
		$this->install($this->settings);

		Logger::getInstance()->log('Complete!', 'green');

	}

	protected function install(array $settings)
	{
		// Prepare the features for the site.
		$features = new FeatureCollection();

		// Initialize the feature collection.
		$features->initialize($settings);

		// Add the site feature.
		$features->append(FeatureFactory::getInstance()->factory('Site', $settings));

		// Add the environment feature.
		$features->append(FeatureFactory::getInstance()->factory($settings['environment'], $settings));

		// Add other features from the settings.
		$this->getFeatures($features, $settings);

		// Handle the configuration for the site.
		Logger::getInstance()->log('Preparing Server Configuration', 'white', Logger::DEBUG_VERBOSE);
		$this->handleConfigurations($features, $settings);

		// Get the copy destination for the files from the feautres.
		// If no feature specifies, we default to the webroot.
		$directory = $settings['webroot_folder'] . '/' . $settings['webroot'];
		$directory = $features->alterHook('alterWebroot', $directory, $directory);

		// Call the pre-copy hook.
		$features->callHook('preCopy', $directory);

		// Copy the files from the repository docroot to the destination.
		Logger::getInstance()->log('Copying docroot', 'white', Logger::DEBUG_VERBOSE);
		$this->copyToWebroot($settings, $directory);

		// Call the post-copy hook.
		$features->callHook('postCopy', $directory);

		// Restart nginx and php5-fpm.
		if ($settings['manage_services']) {
			Logger::getInstance()->log('Restarting Services', 'white', Logger::DEBUG_VERBOSE);
			$this->restartServices();
		}

		// Call the post-restart-services hook.
		$features->callHook('postRestartServices');
	}

	protected function restartServices()
	{
		Executer::getInstance()->exec('service nginx restart');
		Executer::getInstance()->exec('service php5-fpm restart');
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

		Executer::getInstance()->exec("cp -r '$docroot' '$destination'");
	}

	protected function handleConfigurations(FeatureCollection $features, array $settings)
	{
		foreach ($settings['configuration'] as $key => $config) {
			$configuration = ConfigurationManagerFactory::getInstance()->factory($key, $settings);
			if (!($configuration instanceof ConfigurationManager)) continue;
			$configuration->setFeatureCollection($features);
			$configuration->save();
		}
	}

	protected function getFeatures(FeatureCollection &$features, array $settings)
	{
		foreach ($settings['features'] as $feature => $configuration) {
			$features->append(FeatureFactory::getInstance()->factory($feature, $settings));
		}
	}

	public function getScope()
	{
		return ScopeManager::SCOPE_CONTAINER;
	}
}

class MalformedSettingsException extends \Exception {}
class SiteInstallException extends \Exception {}
