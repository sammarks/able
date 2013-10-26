<?php

namespace Able\CommandSets\Core;

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
			->setName('core:update')
			->setDescription('Updates scripts core to the latest version.');

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		parent::execute($input, $output);

		$this->log('Updating scripts core', 'white');
		$this->exec('cd ' . SCRIPTS_ROOT . ' && git stash', false);
		$output = $this->exec('cd ' . SCRIPTS_ROOT . ' && git pull', true, false, true);
		$composer_updated = false;
		foreach ($output as $line) {
			if (strpos($line, 'composer.json') !== false) {
				$composer_updated = true;
				break;
			}
		}
		if ($composer_updated) {
			$succeeded = $this->exec('cd ' . SCRIPTS_ROOT . ' && composer update', false);
			if ($succeeded === false) {
				$this->exec('cd ' . SCRIPTS_ROOT . ' && composer self-update');
				$this->exec('cd ' . SCRIPTS_ROOT . ' && composer update');
			}
		}

		// Update permissions on the able executable.
		$this->exec('chmod a+x ' . SCRIPTS_ROOT . '/able');

		$this->log('Complete!', 'green');

	}

}
