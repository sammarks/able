<?php

namespace Able\CommandSets\Libs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;

class UpdateCommand extends BaseCommand
{

	protected function configure()
	{

		$this
			->setName('libs:update')
			->setDescription('Updates the Able Core Libraries')
			->addArgument('directory',
				InputArgument::OPTIONAL,
				'The directory to update the libs in. Must be a valid Drupal directory.');

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

		$this->log('Updating Core Libraries');

		$repositories = array(
			"{$site_path}/sites/all/modules/contrib/ac_global",
			"{$site_path}/sites/all/themes/contrib/ac_base",
			"{$site_path}/sites/all/themes/contrib/ac_admin",
			"{$site_path}/sites/all/libraries/ac_libs",
		);

		foreach ($repositories as $repository) {
			$this->_update_library($repository);
		}

		$this->log('Complete!', 'green');

	}

	function _update_library($path)
	{
		if (!is_dir($path)) {
			$this->error("The directory '{$path}' does not exist.", false);
			return false;
		}
		$command = "cd {$path} && git pull";
		$this->exec($command);
		return true;
	}

}
