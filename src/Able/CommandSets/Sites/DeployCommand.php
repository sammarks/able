<?php

namespace Able\CommandSets\Sites;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;

use SebastianBergmann\Diff;
use Twitter;
use TwitterException;

class DeployCommand extends BaseCommand
{

	protected function configure()
	{

		$this
			->setName('site:deploy')
			->setDescription('Deploy a Website')
			->addArgument('directory',
				InputArgument::OPTIONAL,
				'The drupal site to deploy to.')
			->addOption('username',
				null,
				InputOption::VALUE_REQUIRED,
				'The SVN username to use.',
				null)
			->addOption('password',
				null,
				InputOption::VALUE_REQUIRED,
				'The SVN password to use.',
				null)
			->addOption('no-notification',
				null,
				InputOption::VALUE_NONE,
				'Don\'t send a notification to Twitter',
				null)
			->addOption('profile-name',
				null,
				InputOption::VALUE_REQUIRED,
				'The name of the site profile.',
				null)
			->addOption('force-makefile',
				null,
				InputOption::VALUE_NONE,
				'Force the makefile to be run.',
				null);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		parent::execute($input, $output);

		$site_path = './';
		if ($input->getArgument('directory')) {
			$site_path = $input->getArgument('directory');
		}

		if (!$this->is_drupal_dir($site_path)) {
			$this->error('The specified directory is not a Drupal directory. The script will now exit.', true);
		}

		// Try and find the profile name.
		$profile_name = '';
		if ($input->getOption('profile-name')) {
			$profile_name = $input->getOption('profile-name');
		} else {
			$directory_listing = $this->exec("ls -a {$site_path}/profiles/ | tr '\\n' '\\n'", true, false, true);
			foreach ($directory_listing as $line) {
				if ($this->strpos_array($line, array('.', '..', 'minimal', 'standard', 'testing')) == false) {
					$profile_name = $line;
					break;
				}
			}
			if ($profile_name == '') {
				$profile_name = $this->prompt('What is the name of the profile for your site?', true);
			} else {
				if (!$this->confirm("Does the profile name '{$profile_name}' sound good?")) {
					$profile_name = $this->prompt('What is it then?', true);
				}
			}
		}

		if ($input->getOption('username')) {
			$username = $input->getOption('username');
		} else {
			$username = $this->prompt('SVN Username?', true);
		}
		if ($input->getOption('password')) {
			$password = $input->getOption('password');
		} else {
			$password = $this->prompt('SVN Password?', true, '', true);
		}

		$this->log("Updating Site");

		$upCommand = "svn up '{$site_path}/profiles/{$profile_name}' --accept theirs-full";
		$upCommand = $this->_appendCredentials($upCommand, $username, $password);
		$success = $this->exec($upCommand, false);
		if ($success === false) {
			$this->log("Error occurred. Recovering...", 'white', self::DEBUG_VERBOSE);
			$command = "svn cleanup '{$site_path}/profiles/{$profile_name}'";
			$command = $this->_appendCredentials($command, $username, $password);
			$this->exec($command);
			$this->exec($upCommand);
		}

		$this->log("Fixing Permissions", 'white', self::DEBUG_VERBOSE);
		$this->exec("chown -R www-data:www-data '{$site_path}/profiles/{$profile_name}'");
		$this->exec("chmod -R 775 '{$site_path}/profiles/{$profile_name}'");

		$this->log("Checking for Module Differences", 'white', self::DEBUG_VERBOSE);
		$org_contents = file_get_contents("{$site_path}/profiles/{$profile_name}/drupal-org.make");
		if (file_exists("{$site_path}/profiles/{$profile_name}/drupal-org.make.old")) {

			// Run the diff, and install new modules/uninstall old modules.
			$old_contents = file_get_contents("{$site_path}/profiles/{$profile_name}/drupal-org.make.old");
			$new_contents = file_get_contents("{$site_path}/profiles/{$profile_name}/drupal-org.make");
			$diff = new Diff;
			$results = $diff->diffToArray($old_contents, $new_contents);

			$modules_to_install = array();
			$modules_to_uninstall = array();

			// Grab the currently installed modules.
			$installed_modules = array();
			$installed_results = $this->exec("cd {$site_path} && drush pm-list --type=module --no-core --pipe",
				true,
				false,
				true);
			if (is_array($installed_results)) {
				$installed_modules = $installed_results;
			}

			foreach ($results as $result) {

				$module_name = $this->_get_module_name($result[0]);
				if (!$module_name) continue;

				// Removed = 2
				// Added = 1

				// If a module was removed and it's currently installed, add it to the "to be removed" list.
				if ($result[1] === 2 && array_search($module_name, $installed_modules) !== false) {
					$modules_to_uninstall[] = $module_name;
				}

				// If a module was removed and it's on the to_install list, remove it from that list.
				$key = array_search($module_name, $modules_to_install);
				if ($result[1] === 2 && $key !== false) {
					unset($modules_to_install[$key]);
				}

				// If a module was added and it's not currently installed, add it to the install list.
				if ($result[1] === 1 && array_search($module_name, $installed_modules) === false) {
					$modules_to_install[] = $module_name;
				}

				// If a module was added and it's on the to uninstall list, remove it from the to uninstall list.
				$key = array_search($module_name, $modules_to_uninstall);
				if ($result[1] === 1 && $key !== false) {
					unset($modules_to_uninstall[$key]);
				}

			}

			// Install new modules and uninstall old modules.
			if (count($modules_to_install) > 0)
				$this->_install_modules($modules_to_install, $site_path);
			if (count($modules_to_uninstall) > 0)
				$this->_uninstall_modules($modules_to_uninstall, $site_path);

		}

		// Update the old file with the new contents.
		file_put_contents("{$site_path}/profiles/{$profile_name}/drupal-org.make.old", $org_contents);

		if ($site_path == './') {
			$site_path = getcwd();
		}

		if ($input->getOption('force-makefile')) {
			$this->log('Running Makefile');
			$this->exec("cd {$site_path} && drush make -y --no-core --working-copy {$site_path}/profiles/{$profile_name}/drupal-org.make");
		}

		$this->log("Posting to Twitter", 'white', self::DEBUG_VERBOSE);
		$site_shortname = pathinfo($site_path, PATHINFO_BASENAME);
		$consumer_key = "AU7vbLvxxSEG2LXsyP6K9A";
		$consumer_secret = "nfy0eUmUsdJH0qKImr1KfTVCpN9fl33nFemTuK30cU";
		$access_token_key = "948419676-bOsoVzIm9md1X8OomFtSaVS3z0LxHHwkeB7WJSfM";
		$access_token_secret = "IqWR8YqMZ67lI1Kv1znzya5bPTnoBRAcT4Djep8Q";
		date_default_timezone_set('America/Louisville');
		$time = date('d M Y \a\t g:i:s a');
		$hostname = gethostname();

		$message = "Repository Deployed: {$site_shortname} on {$hostname} on {$time}";

		$twitter = new Twitter($consumer_key, $consumer_secret, $access_token_key, $access_token_secret);
		try {
			$twitter->send($message);
		} catch (TwitterException $e) {
			$this->error("Could not send to Twitter: " . $e->getMessage(), false);
		}

		$this->log('Updating Redmine Repository...', 'white', self::DEBUG_VERBOSE);
		$this->exec("curl 'http://redmine.ableengine.com/sys/fetch_changesets?key=AtrODTD96soYuvjahedb&id={$profile_name}'",
			false);

		$this->log("Complete!", 'green');

	}

	function _get_module_name($line)
	{
		$matches = array();
		$num_matches = preg_match("^([a-z|_]*)(?=\]\[)(?!projects\[)^", $line, $matches);
		if ($num_matches > 0) {
			return $matches[0];
		} else return false;
	}

	function _install_modules($projects, $directory)
	{
		$projects_str = implode(' ', $projects);
		$this->exec("cd {$directory} && drush pm-download -y {$projects_str}");
	}

	function _uninstall_modules($projects, $directory)
	{
		$projects_str = implode(' ', $projects);
		$this->exec("cd {$directory} && drush pm-disable -y {$projects_str}");
		foreach ($projects as $project) {
			$this->exec("rm -r {$directory}/sites/all/modules/contrib/{$project}");
		}
	}

	function _appendCredentials($command, $username, $password)
	{
		$command .= " --username={$username} --password={$password}";
		$command .= " --trust-server-cert --non-interactive";
		return $command;
	}

}
