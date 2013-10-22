<?php

namespace Able\CommandSets\Sites;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;

class SetupRepoCommand extends BaseCommand
{

	protected function configure()
	{

		$this
			->setName('site:initrepo')
			->setDescription('Initialize a subversion repository in an existing website.')
			->addArgument('url',
				InputArgument::REQUIRED,
				'The URL of the remote Subversion repository. DON\'T include \'/trunk\'.')
			->addArgument('directory',
				InputArgument::OPTIONAL,
				'Path to a Drupal installation.')
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
			->addOption('profile-name',
				null,
				InputOption::VALUE_REQUIRED,
				'The name of the site profile.',
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

		$username = '';
		$password = '';
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

		$this->log("Setting Up Repository");
		$url = $input->getArgument('url');
		$command = "svn checkout {$url}/trunk {$site_path}/profiles/{$profile_name}";
		$command = $this->_appendCredentials($command, $username, $password);
		$this->exec($command);

		$ignore = "drupal-org.make.old\n";
		file_put_contents("{$site_path}/profiles/{$profile_name}/.svnignore", $ignore);

		$this->exec("cd {$site_path}/profiles/{$profile_name} && svn revert *", false);
		$this->exec("cd {$site_path}/profiles/{$profile_name} && svn revert .svnignore", false);
		$this->exec("cd {$site_path}/profiles/{$profile_name} && svn add *", false);
		$this->exec("cd {$site_path}/profiles/{$profile_name} && svn add .svnignore", false);
		$this->exec("cd {$site_path}/profiles/{$profile_name} && svn propset svn:ignore -F '.svnignore' .", false);

		$command = "cd {$site_path}/profiles/{$profile_name} && svn commit -m 'Initial commit.'";
		$command = $this->_appendCredentials($command, $username, $password);
		$this->exec($command);

		$this->log("Deploying for the First Time...");
		$this->exec("able site:deploy {$site_path} --profile-name={$profile_name} --no-notification --username={$username} --password={$password}");

		$this->log("Complete!", 'green');

	}

	function _appendCredentials($command, $username, $password)
	{
		$command .= " --username={$username} --password={$password}";
		$command .= " --trust-server-cert --non-interactive";
		return $command;
	}

}
