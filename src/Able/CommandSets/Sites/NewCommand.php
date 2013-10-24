<?php

namespace Able\CommandSets\Sites;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;
use Able\Helpers\WebServerDetector;

class NewCommand extends BaseCommand
{

	protected function configure()
	{

		$this
			->setName('site:new')
			->setDescription('Create a new site')
			->addArgument('name',
				InputArgument::REQUIRED,
				'The name of the site to create (including www. and .TLD).')
			->addOption('db',
				'd',
				InputOption::VALUE_REQUIRED,
				'The name of the database.',
				null)
			->addOption('db-user',
				null,
				InputOption::VALUE_REQUIRED,
				'The database user for the site.',
				null)
			->addOption('db-pw',
				null,
				InputOption::VALUE_REQUIRED,
				'The database password for the site.',
				null)
			->addOption('admin-pw',
				null,
				InputOption::VALUE_REQUIRED,
				'The default administrator password.',
				null)
			->addOption('root-pw',
				null,
				InputOption::VALUE_REQUIRED,
				'The root database password (you\'ll be asked for this later).',
				null)
			->addOption('profile-name',
				null,
				InputOption::VALUE_REQUIRED,
				'The name to use for the profile. Lowercase and underscores only, or bad things will happen!',
				null)
			->addOption('site-title',
				null,
				InputOption::VALUE_REQUIRED,
				'The title of the site. For example, www.example.com would be \'example\'.',
				null)
			->addOption('site-address',
				null,
				InputOption::VALUE_REQUIRED,
				'The address for the site. For example, www.example.com would be \'example.com\'. No www. prefix.',
				null);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		parent::execute($input, $output);

		$this->log('Preparing');

		$sitename = $input->getArgument('name');
		if (strpos($sitename, ' ') !== false) {
			$this->error('The site name must not contain any spaces.', true);
		}

		$this->log('Generating Site Shortname', 'white', self::DEBUG_VERBOSE);

		$segments = explode('.', $sitename);
		if (count($segments) == 3) {

			$this->log("Looks like you're using a standard domain name (www.example.TLD). Goody!",
				'green',
				self::DEBUG_VERBOSE);
			$site_title = $segments[1];
			$site_address = str_replace('www.', '', $sitename);
			$site_folder = $site_address;

		} else {

			if (!$input->getOption('site-title') || !$input->getOption('site-address')) {
				$this->log("We're going to need some more information to finish the setup of your site.");
			}

			if ($input->getOption('site-title'))
				$site_title = $input->getOption('site-title');
			else
				$site_title = $this->prompt("What is the name of your site? For example, www.example.com would be 'example'.",
					true);

			if ($input->getOption('site-address'))
				$site_address = $this->getOption('site-address');
			else
				$site_address = $this->prompt("What is the address of your site? Without the www. prefix.", true);

			$site_folder = $site_address;

		}

		$sitename = $site_address;
		$site_path = str_replace('[sitename]', $site_folder, $this->config['site']['webroot_pattern']);

		// Remove trailing slash.
		$site_path_segments = explode('/', $site_path);
		$site_path = implode('/', $site_path_segments);

		$this->exec("mkdir -p {$site_path}");

		$this->log('Downloading Drupal', 'white', self::DEBUG_VERBOSE);

		$segments = explode($sitename, $site_path);
		$root_folder = $segments[0];
		$other_folder = $sitename . '/' . $segments[1];
		$this->exec("cd {$root_folder} && drush pm-download drupal -y --drupal-project-rename='{$other_folder}'");

		$this->log('Setting Up the Profile', 'white', self::DEBUG_VERBOSE);

		$profile_name = $site_title;
		if ($input->getOption('profile-name')) {
			$profile_name = $input->getOption('profile-name');
		} else {
			$acceptable = $this->confirm("How does the profile name '{$site_title}' sound? Remember! It must contain only lowercase letters and underscores, otherwise things may break!");
			if (!$acceptable) {
				$profile_name = $this->prompt("What would you like it to be then?", true);
			}
		}

		$this->exec("mkdir {$site_path}profiles/{$profile_name}");

		$info_file = file_get_contents(LIBS_ROOT . '/profile/profile.info');
		$info_file = str_replace('PROFILENAME', $profile_name, $info_file);
		file_put_contents("{$site_path}/profiles/{$profile_name}/{$profile_name}.info", $info_file);

		$profile_file = file_get_contents(LIBS_ROOT . '/profile/profile.profile');
		file_put_contents("{$site_path}/profiles/{$profile_name}/{$profile_name}.profile", $profile_file);

		$profile_install = file_get_contents(LIBS_ROOT . '/profile/profile.install');
		$profile_install = str_replace(
			array('standard_install()'),
			array("{$profile_name}_install()"),
			$profile_install
		);
		file_put_contents("{$site_path}/profiles/{$profile_name}/{$profile_name}.install", $profile_install);

		// Copy the makefile.
		$makefile = file_get_contents(LIBS_ROOT . '/profile/drupal-org.make');
		file_put_contents("{$site_path}/profiles/{$profile_name}/drupal-org.make", $makefile);

		$this->exec("mkdir {$site_path}/profiles/{$profile_name}/modules");
		$this->exec("mkdir {$site_path}/profiles/{$profile_name}/themes");
		$this->exec("mkdir {$site_path}/profiles/{$profile_name}/libraries");

		$this->log('Installing Contrib Modules');
		$this->exec("cd {$site_path} && drush make -y --no-core --working-copy {$site_path}/profiles/{$profile_name}/drupal-org.make");

		$this->log('Installing CKFinder', 'white', self::DEBUG_VERBOSE);
		$this->exec("wget \"http://download.cksource.com/CKEditor%20for%20Drupal/CKEditor%204.0.1%20for%20Drupal/ckeditor_4.0.1_for_drupal_7.zip?drupal-version=on\" --output-document=/tmp/ckeditor.zip");
		$this->exec("unzip /tmp/ckeditor.zip -d '{$site_path}/sites/all/modules/contrib/'");

		$this->log('Preparing for Drupal Installation');
		$this->exec("mkdir '{$site_path}/sites/default/files'");

		$this->log('Fixing Permissions', 'white', self::DEBUG_VERBOSE);
		$this->exec("chmod -R 775 {$site_path}");
		$this->exec("chown -R www-data:www-data {$site_path}");

		$this->log('Gathering Credentials');

		if ($input->getOption('db-user')) {
			$db_username = $input->getOption('db-user');
		} else {

			// Now we generate a username.
			$db_username = $site_title;
			$response = $this->confirm("Is the database username {$db_username} okay? It will be used for the database name as well.");
			if (!$response) {
				$db_username = $this->prompt("What would you like it to be then?", true);
			}

		}

		if ($input->getOption('db')) {
			$db_name = $input->getOption('db');
		} else {
			$db_name = $db_username;
		}

		if ($input->getOption('db-pw')) {
			$db_password = $input->getOption('db-pw');
		} else {

			// Now we generate a password.
			$this->log('Generating a password.', 'white', self::DEBUG_VERBOSE);
			$db_password = str_replace(
					array('a', 'e', 'l', 'o', 's'),
					array('@', '3', '1', '0', '$'),
					$db_username
				) . '!';

		}

		if ($input->getOption('admin-pw')) {
			$admin_password = $input->getOption('admin-pw');
		} else {
			$admin_password = $db_password;
		}

		if ($input->getOption('root-pw')) {
			$root_password = $input->getOption('root-pw');
		} else {
			$root_password = $this->prompt('MySQL root password?', false, '', true);
		}

		// Get the root domain from the configuration.
		$root_domain = $this->config['site']['root_domain'];

		$this->log('Installing Site');
		$command = "cd {$site_path} && drush site-install {$profile_name} -y --db-url='mysql://{$db_username}:{$db_password}@localhost/{$db_name}'
      --site-name={$sitename}
      --account-name='admin'
      --account-pass='{$admin_password}'
      --account-mail='infrastructure@{$root_domain}'
      --db-su='root' --db-su-pw='{$root_password}'";
		$this->exec(str_replace("\n", '', $command));

		$this->log('Patching settings.php', 'white', self::DEBUG_VERBOSE);

		$settings = file_get_contents("{$site_path}/sites/default/settings.php");
		$settings = str_replace(
			array("# \$cookie_domain = '.example.com';"),
			array("\$result = explode(':', \$_SERVER['HTTP_HOST']);
			\$cookie_domain = '.' . \$result[0];"),
			$settings
		);
		file_put_contents("{$site_path}/sites/default/settings.php", $settings);

		$this->log('Enabling and Disabling Modules');

		$modules = $this->config['site']['modules'];

		$enable_modules = implode(' ', $modules['enable']);
		$disable_modules = implode(' ', $modules['disable']);

		$this->exec("cd {$site_path} && drush pm-disable -y {$disable_modules}");
		$this->exec("cd {$site_path} && drush pm-enable -y {$enable_modules}");

		$this->exec("cd {$site_path} && drush variable-set -y admin_theme ac_admin");

		$this->log('Clearing Caches');
		$this->exec("cd {$site_path} && drush cc all");

		$this->log('Generating VirtualHost Configuration', 'white', self::DEBUG_VERBOSE);

		$web_server = WebServerDetector::detect($this);
		$sites_available_base = str_replace('[server]', $web_server, $this->config['server']['available_base']);
		$sites_enabled_base = str_replace('[server]', $web_server, $this->config['server']['enabled_base']);

		$virtualhost_contents = file_get_contents(LIBS_ROOT . "/base.vhost.{$web_server}");
		$new_contents = str_replace(
			array('SITEADDRESS', 'SITENAME', 'SITEFOLDER', 'ROOTDOMAIN'),
			array($site_address, $site_title, $site_folder, $root_domain),
			$virtualhost_contents
		);
		$success = file_put_contents("{$sites_available_base}/{$site_folder}", $new_contents);
		if (!$success) {
			$this->error("There was an error saving the {$web_server} configuration. The script will now exit.", true);
		}
		$this->exec("ln -s '{$sites_available_base}/{$site_folder}' '{$sites_enabled_base}/{$site_folder}'");

		$this->log('Creating Log Folders', 'white', self::DEBUG_VERBOSE);
		$this->exec("mkdir -p '/var/log/nginx/{$site_folder}/'");

		$this->log("Restarting {$web_server}");
		$this->exec("service {$web_server} restart");

		$this->log('Complete!', 'green');
		$this->log('');
		$this->log('Here are your Drupal credentials:');
		$this->log('Username: admin');
		$this->log("Password: {$admin_password}");

	}

}
