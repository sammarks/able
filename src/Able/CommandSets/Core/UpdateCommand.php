<?php

namespace Able\CommandSets\Core;

use Able\Helpers\CommandHelpers\Executer;
use Able\Helpers\CommandHelpers\Logger;
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
		$executer = Executer::getInstance();

		Logger::getInstance()->log('Updating scripts core', 'white');
		$executer->exec('cd ' . SCRIPTS_ROOT . ' && git stash', false);
		$output = $executer->exec('cd ' . SCRIPTS_ROOT . ' && git pull', true, false, true);
		$composer_updated = false;
		foreach ($output as $line) {
			if (strpos($line, 'composer.json') !== false || strpos($line, 'composer.lock') !== false) {
				$composer_updated = true;
				break;
			}
		}
		if ($composer_updated) {
			$succeeded = $executer->exec('cd ' . SCRIPTS_ROOT . ' && composer update', false);
			if ($succeeded === false) {
				$executer->exec('cd ' . SCRIPTS_ROOT . ' && composer self-update');
				$executer->exec('cd ' . SCRIPTS_ROOT . ' && composer update');
			}
		}

		// Update permissions on the able executable.
		$executer->exec('chmod a+x ' . SCRIPTS_ROOT . '/able');

		Logger::getInstance()->log('Complete!', 'green');

	}

}
