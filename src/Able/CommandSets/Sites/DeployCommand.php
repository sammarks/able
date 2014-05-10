<?php

namespace Able\CommandSets\Sites;

use Symfony\Component\Console\Command\Command;
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

		if (!$this->isDrupalDir($site_path)) {
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
		$this->exec("chown -R www-data:www-data '{$site_path}/profiles/{$profile_name}'", false);
		$this->exec("chmod -R 775 '{$site_path}/profiles/{$profile_name}'", false);

		if ($site_path == './') {
			$site_path = getcwd();
		}

		if ($input->getOption('force-makefile')) {
			$this->log('Running Makefile');
			$this->exec("cd {$site_path} && drush make -y --no-core --working-copy {$site_path}/profiles/{$profile_name}/drupal-org.make");
		}

		$this->log("Complete!", 'green');

	}

	function _get_module_name($line)
	{
		$matches = array();
		$num_matches = preg_match('/^([a-z|_]*)(?=\]\[)(?!projects\[)/', $line, $matches);
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
