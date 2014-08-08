<?php

namespace Able\Helpers\Install\Features;

use Able\CommandSets\BaseCommand;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManager;
use Able\Helpers\Install\ConfigurationManagers\ConfigurationManagerFactory;
use Able\Helpers\Install\ConfigurationManagers\Drupal7ConfigurationManager;

class Drupal7Feature extends Feature {

	public function getDependencies()
	{
		return array(
			'PHP',
			'PrettyURLs',
			'Database',
		);
	}

	public function alterWebroot($directory)
	{
		return $directory . '/sites/all/';
	}

	public function preCopy($directory)
	{
		$drupal_root = str_replace('/sites/all/', '', $directory); // Undo the webroot so we can run drush commands.
		$makefile_location = $this->settings['repository_root'] . '/config/Drupal7/drupal-org.make';
		if (!is_file($makefile_location)) {
			throw new Drupal7FeatureException('The drupal-org.make file does not exist at ' . $makefile_location);
		}

		$this->command->log('Running drush makefile.', 'white', BaseCommand::DEBUG_VERBOSE);
		$this->command->exec("drush make --working-copy --prepare-install '$makefile_location' '$drupal_root'");

		$this->command->log('Downloading CKEditor', 'white', BaseCommand::DEBUG_VERBOSE);
		$this->command->exec('wget "http://download.cksource.com/CKEditor%20for%20Drupal/CKEditor%204.0.1%20for%20Drupal/ckeditor_4.0.1_for_drupal_7.zip?drupal-version=on" --output-document=/tmp/ckeditor.zip --no-verbose');
		$this->command->exec("unzip -q /tmp/ckeditor.zip -d '$drupal_root/sites/all/modules/contrib/'");
	}

	public function postCopy($directory)
	{
		$drupal_root = str_replace('/sites/all/', '', $directory); // Undo the webroot so we can run drush commands.

		// Come up with some defaults.
		$db_prefix = !empty($this->configuration['db_prefix']) ? $this->configuration['db_prefix'] : '';
		$site_name = $this->settings['title'];
		$site_mail = $this->settings['email'];
		$account_username = $this->configuration['default_credentials']['username'];
		$account_mail = $this->configuration['default_credentials']['email'];
		$account_pass = $this->configuration['default_credentials']['password'];
		$profile = $this->configuration['profile'];

		// Change to the Drupal root directory.
		$this->command->exec('cd ' . $drupal_root);
		chdir($drupal_root);

		// Bootstrap Drupal and create the settings.php.
		// A lot of this is taken from Drush:commands/core/drupal/site_install.inc

		// Require the Drupal installer.
		define('DRUPAL_ROOT', getcwd());
		define('MAINTENANCE_MODE', 'install');
		require_once $drupal_root . '/includes/install.core.inc';

		// Get the database spec.
		$db_spec = $this->getDatabaseSpec();
		$db_spec['db_prefix'] = $db_prefix;

		$settings = array(
			'parameters' => array(
				'profile' => $profile,
				'locale' => 'en',
			),
			'forms' => array(
				'install_settings_form' => array(
					'driver' => $db_spec['driver'],
					$db_spec['driver'] => $db_spec,
					'op' => 'Save and continue',
				),
				'install_configure_form' => array(
					'site_name' => $site_name,
					'site_mail' => $site_mail,
					'account' => array(
						'name' => $account_username,
						'mail' => $account_mail,
						'pass' => array(
							'pass1' => $account_pass,
							'pass2' => $account_pass,
						),
					),
					'update_status_module' => array(
						1 => false,
						2 => false,
					),
					'clean_url' => true,
					'op' => 'Save and continue',
				),
			),
		);

		try {
			install_drupal($settings);
		} catch (\Exception $ex) {

			// We're only looking for the install already done error.
			// If we don't get it, something else went wrong and we need to show it.
			if ($ex->getMessage() != install_already_done_error()) {
				throw $ex;
			}

		}

		// If the DB didn't exist, enable and disable modules, and then set the defaults.
		if (!$this->checkIfDBExists()) {

			$this->command->log('Changing Modules', 'white', BaseCommand::DEBUG_VERBOSE);
			$this->manageModules($drupal_root);

			$this->command->log('Updating Defaults', 'white', BaseCommand::DEBUG_VERBOSE);
			$this->setDefaults($drupal_root);

		}

		$this->command->log('Preparing Drupal Configuration', 'white', BaseCommand::DEBUG_VERBOSE);
		$this->prepareSettings();

		$this->command->log('Clearing Drupal Caches', 'white', BaseCommand::DEBUG_VERBOSE);
		$this->command->exec("drush cc --root='$drupal_root' -y all");

		$this->command->log('Drupal Installed!', 'white', BaseCommand::DEBUG_VERBOSE);
	}

	protected function getDatabaseFeature()
	{
		/** @var DatabaseFeature $database_feature */
		$database_feature = $this->feature_collection->getFeatureByType('Database');
		if (!$database_feature) {
			throw new \Exception('A database feature could not be found.');
		}

		return $database_feature;
	}

	protected function checkIfDBExists()
	{
		return $this->getDatabaseFeature()->didDatabaseExist();
	}

	/**
	 * Get Database Spec
	 *
	 * Gets the Drupal database spec. Again, this was borrowed heavily from
	 * Drush.
	 *
	 * @return array The database spec.
	 */
	protected function getDatabaseSpec()
	{
		$url = parse_url($this->getDatabaseFeature()->getConnectionString());
		$url += array(
			'driver' => null,
			'user' => null,
			'pass' => null,
			'host' => null,
			'port' => null,
			'path' => null,
			'database' => null,
		);
		$url = (object) array_map('urldecode', $url);

		return array(
			'driver' => $url->scheme == 'mysqli' ? 'mysql' : $url->scheme,
			'username' => $url->user,
			'password' => $url->pass,
			'port '=> $url->port,
			'host' => $url->scheme == 'sqlite' ? '' : $url->host,
			'database' => $url->scheme == 'sqlite' ? $url->host . $url->path : substr($url->path, 1),
		);
	}

	protected function manageModules($drupal_root)
	{
		$to_enable = $this->configuration['modules']['enable'];
		$to_disable = $this->configuration['modules']['disable'];
		$enable_str = implode(' ', $to_enable);
		$disable_str = implode(' ', $to_disable);

		$this->command->exec("drush en --root='$drupal_root' $enable_str -y");
		$this->command->exec("drush dis --root='$drupal_root' $disable_str -y");
	}

	protected function setDefaults($drupal_root)
	{
		$frontend_theme = $this->configuration['themes']['frontend'];
		$admin_theme = $this->configuration['themes']['administration'];

		$this->command->exec("drush variable-set --root='$drupal_root' -y admin_theme $admin_theme");
		$this->command->exec("drush variable-set --root='$drupal_root' -y theme_default $frontend_theme");
	}

	protected function prepareSettings()
	{
		// Get the configuraton manager.
		/** @var Drupal7ConfigurationManager $config_manager */
		$config_manager = ConfigurationManagerFactory::getInstance()->factory('Drupal7', $this->command, $this->settings);
		$config_manager->setFeatureCollection($this->feature_collection);
		$config_manager->save();
	}

	public function getWeight(ConfigurationManager $config)
	{
		if ($config instanceof Drupal7ConfigurationManager) {
			return -100; // We need to make sure the cookie domain can stil be modified by other features.
		}
		return 0;
	}

}

class Drupal7FeatureException extends \Exception {}
