<?php

namespace Able\CommandSets\Sites;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;
use Able\Helpers\WebServerDetector;

class DeleteCommand extends BaseCommand
{

	protected function configure()
	{

		$this
			->setName('site:delete')
			->setDescription('Delete an existing site.')
			->addArgument('name',
				InputArgument::REQUIRED,
				'The name of the site to delete (including www. and .TLD).')
			->addOption('db',
				'd',
				InputOption::VALUE_REQUIRED,
				'The name of the database.',
				null)
			->addOption('root-pw',
				null,
				InputOption::VALUE_REQUIRED,
				'The root database password (you\'ll be asked for this later).',
				null)
			->addOption('db-user',
				null,
				InputOption::VALUE_REQUIRED,
				'The database user for the site.',
				null);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		parent::execute($input, $output);

		$sitename = $input->getArgument('name');

		if (strpos($sitename, ' ') !== false) {
			$this->error('The site name must not contain any spaces.', true);
		}

		$affirmative = $this->confirm("Are you POSITIVE you want to delete this site ({$sitename})? (This is a destructive action!)");
		if (!$affirmative) {
			exit(0);
		}

		$name_confirm = $this->prompt('Just to be sure, type the name of the site.', true);
		if ($name_confirm != $sitename) {
			$this->error('The site name you entered did not match. Code saves the day, again.', true);
		}

		$this->log("If you insist... Deleting site ({$sitename})", 'yellow');

		$sitepathname = str_replace('www.', '', $input->getArgument('name'));

		$web_server = WebServerDetector::detect($this);
		$sites_available_base = str_replace('[server]', $web_server, $this->config['server']['available_base']);
		$sites_enabled_base = str_replace('[server]', $web_server, $this->config['server']['enabled_base']);

		$this->log('Attempting to remove VirtualHost entry');
		$item_deleted = false;
		if (file_exists("{$sites_enabled_base}/{$sitepathname}")) {
			$item_deleted = true;
			unlink("{$sites_enabled_base}/{$sitepathname}");
		}
		if (file_exists("{$sites_available_base}/{$sitepathname}")) {
			$item_deleted = true;
			unlink("{$sites_available_base}/{$sitepathname}");
		}

		if ($item_deleted) {
			$this->log("Restarting {$web_server}");
			$this->exec("service {$web_server} restart");
		}

		$this->log("Attempting to delete the database");

		if ($input->getOption('db')) {
			$db_name = $input->getOption('db');
		} else {

			// Now we generate a username.
			$this->log('Generating a database name', 'white', self::DEBUG_VERBOSE);
			$segments = explode('.', $sitename);
			if (array_key_exists(1, $segments)) {
				$db_name = $segments[1];
				$response = $this->confirm("Does the database name {$db_name} sound right?");
				if (!$response) {
					$db_name = $this->prompt("What is it supposed to be then?", true);
				}
			} else {
				$db_name = $this->prompt("What is the database name?", true);
			}

		}

		if ($input->getOption('db-user')) {
			$db_username = $input->getOption('db-user');
		} else {

			// Now we generate a username.
			$db_username = $db_name;
			$response = $this->confirm("Does the database user {$db_username} sound right?");
			if (!$response) {
				$db_username = $this->prompt("What is it supposed to be then?", true);
			}

		}

		if ($input->getOption('root-pw')) {
			$root_password = $input->getOption('root-pw');
		} else {
			$root_password = $this->prompt('MySQL root password?', false, '', true);
		}

		$database_query = "DROP DATABASE IF EXISTS {$db_name};";
		$user_query = "DROP USER '{$db_username}'@'localhost';";

		$this->exec("mysql -u root --password='{$root_password}' --execute='{$database_query}'");
		$result = $this->exec("mysql -u root --password='{$root_password}' --execute='{$user_query}'", false);
		if ($result === false) {
			$this->log("It's okay! The user didn't exist in the first place, so there is none to delete!",
				'green',
				self::DEBUG_VERBOSE);
		}

		$this->log('Attempting to delete site folder');

		$site_path = str_replace('[sitename]', $sitepathname, $this->config['site']['webroot_pattern']);
		if (is_dir($site_path)) {
			$this->exec("rm -rf '{$site_path}'");
		}

	}

}
